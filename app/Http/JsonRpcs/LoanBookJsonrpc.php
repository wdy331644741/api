<?php
namespace App\Http\JsonRpcs;
use App\Models\LoanBook;
use App\Exceptions\OmgException as OmgException;

class LoanBookJsonRpc extends JsonRpc {
    
    /**
     *  测试 
     *
     * @JsonRpcMethod
     */
    public function addLoan($params) {
        $data = array();
        $data['name'] = trim($params->name);
        if(empty($data['name'])){
            throw new OmgException(OmgException::VALID_NAME_FAIL);
        }
        $data['phone'] = trim($params->phone);
        if(empty($data['phone'])){
            throw new OmgException(OmgException::VALID_PHONE_FAIL);
        }
        $data['city'] = trim($params->city);
        if(empty($data['city'])){
            throw new OmgException(OmgException::VALID_CITY_FAIL);
        }
        $data['collateral'] = trim($params->collateral);
        if(empty($data['collateral'])){
            throw new OmgException(OmgException::VALID_COLLATERAL_FAIL);
        }
        $data['amount'] = trim($params->amount);
        if(empty($data['amount'])){
            throw new OmgException(OmgException::VALID_AMOUNT_FAIL);
        }
        $data['is_read'] = 0;
        //添加时间
        $data['created_at'] = date("Y-m-d H:i:s");
        //修改时间
        $data['updated_at'] = date("Y-m-d H:i:s");
        $id = LoanBook::insertGetId($data);
        if($id){
            return array(
                'code' => 0,
                'message' => 'success',
                'data' => $id
            );
        }
        throw new OmgException(OmgException::INSERT_FAIL);
    }
}
