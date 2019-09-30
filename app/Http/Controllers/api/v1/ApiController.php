<?php
/**
 * Created by PhpStorm.
 * User: saeed
 * Date: 1/20/2019
 * Time: 12:28 PM
 */

namespace App\Http\Controllers\api\v1;
use App\Http\Controllers\Controller;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Response;



class ApiController extends Controller
{
    protected $statusCode = 200;

    /**
     * @return mixed
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @param mixed $statusCode
     * @return $this
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    public function respondNotFound($message = 'Not Found',$data = [])
    {

        return $this->setStatusCode(404)->respondWithMessage($message,$data);
    }

    public function respondBadRequest($message = 'Not Found',$data = [])
    {
        return $this->setStatusCode(421)->respondWithMessage($message,$data);
    }

    public function respondCriticalError($message = 'Critical Error',$data = [])
    {
        return $this->setStatusCode(421)->respondWithMessage($message,$data);
    }

    public function respondNotLogin($message = 'You must first log in to your account',$data = [])
    {
        return $this->setStatusCode(403)->respondWithMessage($message,$data );
    }

    public function respondServerSideError($message = 'Sorry, Please call the administrator',$data = [])
    {
        return $this->setStatusCode(500)->respondWithMessage($message,$data );
    }

    public function respondSuccessfully($message = 'Successfully Done',$data = [])
    {
        return $this->respondWithMessage($message , $data);
    }


    public function respond($data , $headers = [])
    {
        return Response::json($data, $this->getStatusCode(),$headers);
    }

    public function respondWithMessage($message, $data = [])
    {
        return $this->respond([
            'result_message'=> $message,
            'result_code' => $this->getStatusCode(),
            'data' => $data
        ]);
    }

    protected function mergeDataWithPaginationInfo(LengthAwarePaginator $obj, $data){
        $data = array_merge($data, [
            'pagination' => [
                'total_count' => $obj->total(),
                'total_pages' => (int) ceil($obj->total() / $obj->perPage()),
                'current' => $obj->currentPage(),
                'limit' => $obj->perPage()
            ]
        ]);
        return $data;
    }


    /**
     * @param $to
     * @param $message
     */
    public function sendSMS($to, $message)
    {
        $client = new \GuzzleHttp\Client;
        $client->request('GET', "http://37.130.202.188/class/sms/webservice/send_url.php?from=+98100020400&to=$to&msg=" . urlencode($message) . "&uname=hamidreza9213&pass=" . urlencode("haj hamid"));
    }
}
