<?php

class KalinkaTestCase extends \PHPUnit_Framework_TestCase
{
    const X1 = "TESTCASE_CALL_ARG_SLOT_1";
    const X2 = "TESTCASE_CALL_ARG_SLOT_2";
    const X3 = "TESTCASE_CALL_ARG_SLOT_3";

    protected function assertCallsEqual($callable, $argsTemplate, $tbl)
    {
        $slots = [self::X1, self::X2, self::X3];

        foreach ($tbl as $idx => $row) {
            $args = $argsTemplate;
            foreach ($args as $argIdx => $arg) {
                foreach ($slots as $slotIdx => $slot) {
                    if ($arg === $slot) {
                        $args[$argIdx] = $row[$slotIdx+1];
                    }
                }
            }

            $this->assertEquals(
                $row[0],
                call_user_func_array($callable, $args),
                "Table row: $idx"
            );
        }
    }
}
