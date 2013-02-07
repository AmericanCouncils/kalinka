<?php

namespace Kalinka;

/**
 * Access class that combines results from one or more other
 * constituent access classes.
 *
 * Each permission check involves only those access classes that
 * claim the given action, objectType, and property as valid.
 * So if only one of the constituents has any information about
 * Comment objects, then permissions results for checks on Comments
 * will come only from that constituent.
 */
class DelegatedAccess extends BaseAccess
{
    private $constituents;

    // TODO Assert that constituents all implement BaseAccess
    /**
     * Instantiate a new DelegatedAccess with the given constituents,
     * which all must be instances of classes that are descendants of
     * BaseClass.
     *
     * @param $constituents A list of access objects.
     */
    public function __construct($constituents)
    {
        $this->constituents = $constituents;
    }

    protected function isValidAction($action)
    {
        foreach ($this->constituents as $c) {
            if ($c->isValidAction($action)) {
                return true;
            }
        }
        return false;
    }

    protected function isValidObjectType($objectType)
    {
        foreach ($this->constituents as $c) {
            if ($c->isValidObjectType($objectType)) {
                return true;
            }
        }
        return false;
    }

    protected function isValidProperty($objectType, $property)
    {
        foreach ($this->constituents as $c) {
            if ($c->isValidProperty($objectType, $property)) {
                return true;
            }
        }
        return false;
    }

    protected function getPrivileges($action, $objectType, $property)
    {
        $privs = [];
        foreach ($this->constituents as $c) {
            if (
                $c->isValidAction($action) &&
                $c->isValidObjectType($objectType) &&
                (is_null($property) || $c->isValidProperty($objectType, $property))
            ) {
                $privs[] = $c->getPrivileges($action, $objectType, $property);
            }
        }
        return $privs;
    }
}
