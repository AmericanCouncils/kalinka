<?php

namespace AC\Kalinka\Authorizer;

use AC\Kalinka\Guard\GuardInterface;

/**
 * Interface for Authorizer classes, which grant or deny access to resources.
 */
interface AuthorizerInterface
{
   /**
    * Decides if an action on a resource is permitted.
    *
    * @param $action The action that we want to check, a string
    * @param $resType The resource type we're checking access to, a string
    * @param $object (Optional) The target object to pass to the Guard's
    *                     checkPolicy method. This can be `null`,
    *                     e.g. if this is a "virtual" resource
    *                     (see Guard\BaseGuard for
    *                     more information on this).
    * @return Boolean
    */
   public function can($action, $resType, $object = null);

   /**
    * Register a guard instance.
    *
    * @param  string         $name  Name of guard.
    * @param  GuardInterface $guard The guard instance
    */
   public function registerGuard($name, GuardInterface $guard);
}
