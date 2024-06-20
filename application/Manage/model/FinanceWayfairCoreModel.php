<?php

namespace app\Manage\model;

use think\Model;

class FinanceWayfairCoreModel extends Model
{
    protected $name = 'finance_wayfair_core';

    protected $resultSetType = 'collection';

    static public function formatExcelStatus($status): int
    {
        return $status == "PENDING PAYMENT" ? 1 : 0;
    }

    static public function formatExcelUserAccount($userAccount): string
    {
        if ($userAccount == "1店") {
            return "WAYFAIR_JG";
        } elseif ($userAccount == "2店") {
            return "Wayfair_Carajali";
        } else {
            return "";
        }
    }
}
