<?php
namespace app\Manage\controller;

use Ak\OpenAPI\Exception\InvalidAccessTokenException;
use Ak\OpenAPI\Exception\InvalidResponseException;
use Ak\OpenAPI\Exception\RequestException;
use Ak\OpenAPI\Exception\RequiredParamsEmptyException;
use app\Manage\model\AkOpenAPI;

class TestController extends BaseController
{
    /**
     * @throws RequiredParamsEmptyException
     * @throws RequestException
     * @throws InvalidResponseException
     * @throws InvalidAccessTokenException
     */
    public function index()
    {
        $params = [
            'offset'        =>  0,
            'length'        =>  1000,
            'startDate'     =>  '2024-02',
            'endDate'       =>  '2024-02',
            'monthlyQuery'  =>  true,
//            'searchField'   =>  'seller_sku',
//            'searchValue'   =>  'BBK015MZ-RETURN'
        ];
        $res = AkOpenAPI::makeRequest("/bd/profit/report/open/report/msku/list", "POST", $params);
        dump($res);
        exit();
    }
}
