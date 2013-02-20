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

Create a base Guard class for your app. Guards are where you
define your security policies. Policies are just boolean-retuning methods
whose names start with "policy". Here is an example Guard
base class for your app:

```php
use AC\Kalinka\Guard\BaseGuard;

class MyAppBaseGuard extends BaseGuard
{
    protected function policyAdmin()
    {
        return $this->subject->isAdmin();
    }
}
```

The `subject` above is the user whose privileges are being checked. This can
be an instance of your app's User class (as the code above assumes), or
it can be something as simple as the name of the user as a string. The important
thing is that it lets you find out everything you need about the user to
determine what they're allowed to do.

Guards may also have an `object`, which is the resource that the subject is trying to get
at.  You'll need to write a Guard class for each specific type of resource
that has special policies. For example, suppose you have Documents that allow
access based on whether or not the user owns that particular document, and/or
whether or not the document is "unlocked":

```php
class DocumentGuard extends MyAppBaseGuard
{
    protected function policyDocumentOwner()
    {
        return ($this->object->getOwnerId() == $this->subject->getId());
    }

    protected function policyDocumentUnlocked()
    {
        return !($this->object->isLocked());
}
```

When your app wants to do some actual access checks, these are done through
an Authorizer. The Authorizer determines what Guard policies are applied
in any given situation. You can reference the policies implemented
in your Guards, as well as the default "allow" policy that simply always
permits access.

If you don't define any policy for an action, it is always denies access by default.

For most basic use cases, you can derive from `SimpleAuthorizer`:

```php
use AC\Kalinka\Authorizer\SimpleAuthorizer;

class MyAppAuthorizer extends SimpleAuthorizer
{
    public function __construct($subject)
    {
        parent::__construct($subject);

        $this->registerGuards([
            "document" => "MyApp\Guards\DocumentGuard",
            "comment" => "MyApp\Guards\MyAppBaseGuard",
            // ... and so on for all your protected resources
        ]);

        $this->registerActions([
            "document" => ["read", "write"],
            "comment" => ["read", "create", "delete"],
            // ...
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

Notice how comments are handled by `MyAppBaseGuard`; we did not have to
write a new `CommentGuard` class. We don't have any policies that care about
the content of comments, so there's no need to write a special
class for guarding them.

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
extend from `RoleAuthorizer`, which provides a `registerRolePolicies()` method:

```php
use AC\Kalinka\Authorizer\RoleAuthorizer;

class MyAppAuthorizer extends RoleAuthorizer
{
    public function __construct(MyUserClass $user)
    {
        $roleNames = [];
        foreach ($user->getRoles() as $role) {
            $roleNames[] = $role->getName();
        }
        parent::__construct($roleNames, $user);

        $this->registerGuards([
            "document" => "MyApp\Guards\DocumentGuard",
            "comment" => "MyApp\Guards\MyAppBaseGuard",
            // ... and so on for all your protected resources
        ]);

        $this->registerActions([
            "document" => ["read", "write"],
            "comment" => ["read", "create", "delete"],
            // ...
        ]);

        $this->registerRolePolicies([
            "guest" => [
                "document" => [
                    "read" => "allow",
                ],
                "comment" => [
                    "read" => "allow",
                ]
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
            "admin" => [
                "document" => [
                    "read" => "allow",
                    "write" => "documentUnlocked"
                ],
                "comment" => [
                    "read" => "allow",
                    "create" => "allow",
                    "delete" => "allow"
                ]
            ]
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
