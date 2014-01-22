# Kalinka

Kalinka helps you determine who's allowed to do what in your app.

[API Documentation](http://americancouncils.github.com/kalinka/annotated.html)

This library is for *authorization*, not *authentication*. You'll need a
different library for actually logging your users in and out, managing
passwords, OpenID, and so on. Kalinka is for
determining what actions are available to your users after they've logged in.

## Installation

You can get Kalinka via composer:

    "require": {
        ...
        "ac/kalinka": "dev-master"
    }

## Getting Started

Create a base abstract Guard class for your app. Guards are where you
define your security policies. Policies are just boolean-retuning methods
whose names start with "policy". Here is an example:

```php
use AC\Kalinka\Guard\BaseGuard;

abstract class MyAppBaseGuard extends BaseGuard
{
    protected function policyAdmin($subject)
    {
        return $subject->isAdmin();
    }
}
```

The `subject` above is the user whose privileges are being checked. This can
be an instance of your app's User class (as the code above assumes), or
it can be something as simple as the name of the user as a string. The important
thing is that it lets you find out everything you need about the user to
determine what they're allowed to do.

`MyAppBaseGuard` is abstract because it doesn't define any actions. Actions
are strings which represent various things the subject might want to do,
and which you may or may not permit. For your more specific Guard
classes, you'll need to provide a `getActions` method which returns an array:

```php
class DocumentGuard extends MyAppBaseGuard
{
    public function getActions()
    {
        return ["read", "write"];
    }
}
```

Policies may also accept an `object` as their second
argument, which is a specific resource that the subject is trying to get at.
For example, suppose you have Documents that allow access based on whether or
not the user owns that particular document, and/or whether the document is
"unlocked":

```php
class DocumentGuard extends MyAppBaseGuard
{
    public function getActions()
    {
        return ["read", "write"];
    }

    protected function policyDocumentOwner($subject, $object)
    {
        return ($object->getOwnerId() == $subject->getId());
    }

    protected function policyDocumentUnlocked($subject, $object)
    {
        return !($object->isLocked());
    }
}
```

When your app wants to do some actual access checks, these are done through
an Authorizer. The Authorizer determines what Guard policies are applied
in any given situation. You can reference the policies implemented
in your Guards, as well as the default "allow" policy that simply always
permits access.

If you don't define any policy for an action, it is always denied by default.

For most basic use cases, you can derive from `SimpleAuthorizer`:

```php
use AC\Kalinka\Authorizer\SimpleAuthorizer;
use MyApp\Guards\CommentGuard;
use MyApp\Guards\DocumentGuard;

class MyAppAuthorizer extends SimpleAuthorizer
{
    public function __construct($subject)
    {
        parent::__construct($subject);

        $this->registerGuards([
            "document" => new DocumentGuard,
            "comment" => new CommentGuard,
            // ... and so on for all your protected resources
        ]);

        $this->registerPolicies([
            "document" => [
                "read" => "allow",
                "write" => "documentOwner"
            ],
            "comment" => [
                "read" => "allow",
                "create" => "allow",
                "delete" => "admin"
            ],
            // ...
        ]);
    }
}
```

Now after all that, we're ready to do authorization! Whenever you want to check
if access to some resource is allowed, just create an instance
of your Authorizer class and call the `can` method:

```php
use MyApp\MyAppAuthorizer;

$auth = new MyAppAuthorizer($currentUser);
if ($auth->can("write", "document", $someDocument)) {
    $someDocument->setContent($newValue);
} else {
    print "Access denied!\n";
}
```

## Combining Policies

Suppose that you only want to allow documents to be edited only if they are
unlocked *and* the user owns them. This can be done by supplying a list
of policies instead of a single string:

```php
// ...
$this->registerPolicies([
    "document" => [
        "read" => "allow",
        "write" => [
            "documentUnlocked",
            "documentOwner"
        ]
    ],
    // ...
]);
```

Sometimes an action is permitted if any one
of several different policies allows it, even if the others do not. Suppose
that for the purposes of writing documents, being an admin is as good as
being the document's owner, but the rule about the document being unlocked
still applies to everyone. In that case, you can use an inner list:

```php
// ...
$this->registerPolicies([
    "document" => [
        "read" => "allow",
        "write" => [
            "documentUnlocked",
            ["documentOwner", "admin"]
        ]
    ],
    // ...
]);
```

The principle here is that the outer list is AND-connected, while inner lists
are OR-connected.

If you need something even more complicated than that, you could always
implement it as its own policy. Policies can call each other with the
`checkPolicy()` method on `BaseGuard`.

## Roles

`SimpleAuthorizer` will only work for very straightforward setups, where the same
policies apply to everyone. In real systems, it's more common
that you have a bunch of different roles that people can belong to, each of
which has access to resources under
a variety of different circumstances. The easiest way to accomplish this is to
extend from `RoleAuthorizer`, which provides a `registerRolePolicies()` method
that replaces the functionality of `SimpleAuthorizer`'s `registerPolicies()` method:

```php
use AC\Kalinka\Authorizer\RoleAuthorizer;
use MyApp\Guards\CommentGuard;
use MyApp\Guards\DocumentGuard;

class MyAppAuthorizer extends RoleAuthorizer
{
    public function __construct(MyUserClass $user)
    {
        $roleNames = [];
        foreach ($user->getRoles() as $role) {
            $roleNames[] = $role->getName();
        }
        parent::__construct($user, $roleNames);

        $this->registerGuards([
            "document" => new DocumentGuard,
            "comment" => new CommentGuard,
            // ... and so on for all your protected resources
        ]);

        $this->registerRolePolicies([
            "guest" => [
                "document" => [
                    "read" => "allow",
                ],
                "comment" => [
                    "read" => "allow",
                ]
                // ...
            ],
            "customer" => [
                "document" => [
                    "read" => "allow",
                    "write" => [
                        "documentUnlocked",
                        "documentOwner"
                    ]
                ],
                "comment" => [
                    "read" => "allow",
                    "create" => "allow",
                ],
                // ...
            ],
            // ...
        ]);
    }
}
```

The roles are supplied as a list of strings. When a permissions check
is made, each role is tried individually; if any role assigned to the
user allows the action, then it is allowed overall.

This is a much more flexible solution than adding role-like policies
to your Guards, as we did above with the `policyAdmin()` method of
`MyAppBaseGuard`.

## Partially Included Roles

You may sometimes have special situations where the desired permissions don't
match up perfectly with your roles. For example, you might have a user who has
all the rights of the "contributor" role, but also can act as an "administrator"
when it comes to manipulating comments. You can handle this situation with your
`RoleAuthorizer` derivative by using the `registerRoleInclusions()` method:

```php
use AC\Kalinka\Authorizer\RoleAuthorizer;

class MyAppAuthorizer extends RoleAuthorizer
{
    public function __construct(MyUserClass $user)
    {
        // ...

        if ($user->isCommentAdmin()) {
            $this->registerRoleInclusions([
                "comment" => "administrator"
            ]);
        }
    }
}
```

It is also possible to include only particular actions from a role:

```php
$this->registerRoleInclusions([
    "comment" => ["update" => "administrator", "delete" => "administrator"]
]);
```

These included sections are treated as though they were another role; access
is permitted if any included policy lists approve it, *or* if any of the policy
lists from the user's regular roles approve it.
