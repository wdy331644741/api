<?php
/**
 * User: neil
 * Date: 16/5/5
 * Time: 下午2:18
 */

namespace App\Exceptions;
use Exception;

class CustomException extends Exception
{
    public $code;
    public $status;
    /**
     * The validator instance.
     *
     * @var \Illuminate\Validation\Validator
     */
    //public $validator;

    /**
     * The recommended response to send to the client.
     *
     * @var \Illuminate\Http\Response|null
     */
    public $response;

    /**
     * Create a new exception instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @param  \Illuminate\Http\Response  $response
     * @return void
     */
    public function __construct($response = null, $code = 20000, $status = 200)
    {
        parent::__construct($response);

        $this->response = $response;
        $this->code = $code;
        $this->status = $status;
    }

    /**
     * Get the underlying response instance.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getResponse()
    {
        return $this->response;
    }
}


