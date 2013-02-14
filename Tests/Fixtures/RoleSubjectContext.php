<?php

namespace Fixtures;

use AC\Kalinka\Context\BaseContext;

class RoleSubjectContext extends BaseContext
{
    public function __construct($subject, $object = null) {
        if (is_string($subject)) {
            $subject = [$subject];
        }
        parent::__construct($subject, $object);
    }

    protected function isValidSubject()
    {
        return is_array($this->subject);
    }

    protected function policyNonGuest()
    {
        return (array_search("guest", $this->subject) === FALSE);
    }
}
