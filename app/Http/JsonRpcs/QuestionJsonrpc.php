<?php

namespace App\Http\JsonRpcs;
use App\Exceptions\OmgException;
use App\Models\Category;
use App\Models\CategoryQuestion;
use App\Models\Question;
use Lib\JsonRpcClient;
use Validator;
use Config;
use Illuminate\Pagination\Paginator;




class QuestionJsonrpc extends JsonRpc {

    public function __construct()
    {
        global $userId;
        $this->userId = $userId;
    }

    /**
     *  常见问题列表
     *
     * @JsonRpcMethod
     */
    public function getOftenQuestions($params){

        if (empty($this->userId)) {
            throw  new OmgException(OmgException::NO_LOGIN);
        }
        $page = isset($params->page) ? $params->page : 1;
        $pageNum = isset($params->pageNum) ? $params->pageNum : 3;
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });
        $data = Category::select(['id','title', 'icon'])->where(['status'=> 1])->orderBy('id', 'desc')->paginate($pageNum);
        foreach ($data as $k=>$v) {
            $qids = CategoryQuestion::select('q_id')->where(['c_id'=>$v->id])->get()->toArray();
            $qids = array_column($qids, 'q_id');
            if ($qids) {
                $v->question = Question::select(['id', 'title'])->where(['status'=> 1])->whereIn('id', $qids)->orderBy('id', 'desc')->get();
            } else {
                $v->question = [];
            }
        }
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $data
        );
    }

    /**
     *  问题详情
     *
     * @JsonRpcMethod
     */
    public function getQuestionsDetail($params){
        if (empty($this->userId)) {
            throw  new OmgException(OmgException::NO_LOGIN);
        }

        if (empty($params->id)) {
            throw  new OmgException(OmgException::PARAMS_ERROR);
        }

        $data = Question::select(['id','title', 'content'])->find($params->id);
        if (!$data) {
            throw  new OmgException(OmgException::DATA_ERROR);
        }
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $data
        );
    }

    /**
     *  问题关联
     *
     * @JsonRpcMethod
     */
    public function getRelativeQuestions($params){
        if (empty($this->userId)) {
            throw  new OmgException(OmgException::NO_LOGIN);
        }

        if (empty($params->id)) {
            throw  new OmgException(OmgException::PARAMS_ERROR);
        }

        $question = Question::find($params->id) ;
        if (!$question) {
            throw  new OmgException(OmgException::DATA_ERROR);
        }
        $data = [];
        if($question->relative) {
               $qids = json_decode($question->relative, true);
               $data = Question::select(['id', 'title'])->where(['status'=> 1])->whereIn('id', $qids)->get()->toArray();
        }
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $data
        );
    }
}

