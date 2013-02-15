# Kalinka

Kalinka helps you determine who's allowed to do what in your app.

Kalinka is for *authorization*, not *authentication*. You'll need a
different library for actually logging your users in and out. Kalinka is for
determining what actions are available to your users after they've logged in.

## Installation

You can get Kalinka via composer:

    "require": {
        ...
        "ac/kalinka": "dev-master"
    }

## Getting Started

Create a base Guard class for your app. Guards
are where you define your security policies. Policies are just boolean-retuning methods
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
thing is that it's everything about the user you need to know in order to
determine if they're allowed to do something.

Guards may also have an `object`, which is the resource that the subject is trying to get
at.  You'll need an additional Guard for each specific type of resource
that has special policies. For example, suppose you have Documents that are
only meant to be editable by user that created them:

```php
class DocumentGuard extends MyAppBaseGuard
{
    protected function policyDocumentOwner()
    {
        return ($this->object->getOwnerId() === $this->subject->getId());
    }
}
```

When your app wants to do some actual access checks, these are done through
an Authorizer. The Authorizer determines what Guard policies are applied
in any given situation. You can reference the policies implemented
in your Guards, as well as the default "allow" policy that simply always
permits access.

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

Notice how comments are handled by `MyAppBaseGuard`, instead of having to
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

## Roles and Policy Combinations

    TODO Proper documentation on this

The above system will only work for fairly simple setups, where the same
policy applies to everyone. In real systems, it's more common
that you have a bunch of different roles that
people can belong to, each of which has access to certain sorts of things under
a variety of circumstances.

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
    }
}
```
