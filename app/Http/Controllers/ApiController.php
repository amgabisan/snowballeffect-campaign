<?php

namespace App\Http\Controllers;

require base_path('vendor/campaignmonitor/createsend-php/csrest_general.php');
require base_path('vendor/campaignmonitor/createsend-php/csrest_clients.php');
require base_path('vendor/campaignmonitor/createsend-php/csrest_subscribers.php');
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Session;

class ApiController extends Controller
{
    // Connect Application to Campaign Monitor
    public function index()
    {
        $authorize_url = \CS_REST_General::authorize_url(
            env('CAMPAIGNMONITOR_CLIENT_ID', null),
            'http://amg.com/success',
            'ViewReports,ManageLists'
        );

        return Redirect::to($authorize_url);
    }

    public function success()
    {
       $result = \CS_REST_General::exchange_token(
            env('CAMPAIGNMONITOR_CLIENT_ID', null),
            env('CAMPAIGNMONITOR_CLIENT_SECRET', null),
            'http://amg.com/success',
            $_GET['code']
        );

        if ($result->was_successful()) {
            $accessToken = $result->response->access_token;
            $expiresIn = $result->response->expires_in;
            $refreshToken = $result->response->refresh_token;

            $this->setAuth($accessToken, $refreshToken, $expiresIn);

            return redirect('/dashboard');
        } else {
            echo 'An error occurred:\n';
            echo $result->response->error.': '.$result->response->error_description."\n";
            # Handle error...
            return redirect('/error');
        }
    }

    public function dashboard()
    {
        if (!Session::has('auth')) {
            return redirect('/');
        }

        return view('api.index');
    }

    public function details(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $result = $this->callApi('get_clients');
        $snowballEffect = array_shift($result->response);

        /* Getting a Client Detail - Snowball effect */
        $clientDetails = $this->callClientApi($snowballEffect->ClientID, 'get')->response;

        /* Getting a Subscriber List - Final retail newsletter (do not delete) */
        $subscriberListAll = $this->callClientApi($snowballEffect->ClientID, 'list')->response;
        $subscriber = array_shift($subscriberListAll);

        // Get Subscriber Details
        $emailAddress = $_POST['email'];
        $searchedSubscriber = $this->callSubscriberApi($subscriber->ListID, 'get', $emailAddress);

        if ($searchedSubscriber->http_status_code == 200) {
            $searchedSubscriber = $searchedSubscriber->response;

            // Get Subscriber History
            $subscriberHistory = $this->callSubscriberApi($subscriber->ListID, 'get_history', $emailAddress);
        } else {
            $searchedSubscriber = $subscriberHistory = null;
            // Subscriber not found.
        }

        //dd($clientDetails, $subscriber, $searchedSubscriber, $subscriberHistory);
        return view('api.index', compact('searchedSubscriber', 'subscriberHistory', 'snowballEffect'));
    }

    private function setAuth($accessToken, $refreshToken, $expiresIn)
    {
        $auth = array(
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in'    => $expiresIn
        );

       session(['auth' => $auth]);
    }

    private function getAuth()
    {
        return session('auth');
    }

    private function callApi($functionCall)
    {
        if (!Session::has('auth')) {
            return false;
        }

        $auth = $this->getAuth();

        $wrap = new \CS_REST_General($auth);

        if ($functionCall == 'get_clients') {
            $result = $wrap->get_clients();
        } else {
            $result = $wrap->get_clients();
        }

        if (!$result->was_successful()) {
            # If you receive '121: Expired OAuth Token', refresh the access token
            if ($result->response->Code == 121) {
                list($new_access_token, $new_expires_in, $new_refresh_token) = $wrap->refresh_token();
                $accessToken = $new_access_token;
                $expiresIn = $new_expires_in;
                $refreshToken = $new_refresh_token;
            }
            # Make the call again
            $this->callApi($functionCall);
        }

        return $result;
    }

    private function callClientApi($clientID, $functionCall)
    {
        if (!Session::has('auth')) {
            return false;
        }

        $auth = $this->getAuth();

        $wrap = new \CS_REST_Clients($clientID, $auth);

        if ($functionCall == 'get') {
            $result = $wrap->get();
        } else if ($functionCall == 'list') {
            $result = $wrap->get_lists();
        }

        if (!$result->was_successful()) {
            # If you receive '121: Expired OAuth Token', refresh the access token
            if ($result->response->Code == 121) {
                $csrestgeneral = new \CS_REST_General($auth);
                list($new_access_token, $new_expires_in, $new_refresh_token) = $csrestgeneral->refresh_token();
                $accessToken = $new_access_token;
                $expiresIn = $new_expires_in;
                $refreshToken = $new_refresh_token;

                $this->setAuth($accessToken, $refreshToken, $expiresIn);
            }
            # Make the call again
            $this->callApi($clientID, $functionCall);
        }

        return $result;
    }

    private function callSubscriberApi($listId, $functionCall, $param=null)
    {
        if (!Session::has('auth')) {
            return false;
        }

        $auth = $this->getAuth();

        $wrap = new \CS_REST_Subscribers($listId, $auth);

        if ($functionCall == 'get_clients') {
            $result = $wrap->get($param);
        } else if ($functionCall == 'get_history') {
            $result = $wrap->get_history($param);
        } else {
            $result = $wrap->get($param);
        }

        if (!$result->was_successful()) {
            # If you receive '121: Expired OAuth Token', refresh the access token
            if ($result->response->Code == 121) {
                $csrestgeneral = new \CS_REST_General($auth);
                list($new_access_token, $new_expires_in, $new_refresh_token) = $csrestgeneral->refresh_token();
                $accessToken = $new_access_token;
                $expiresIn = $new_expires_in;
                $refreshToken = $new_refresh_token;

                $this->setAuth($accessToken, $refreshToken, $expiresIn);
            }
            # Make the call again
            $this->callApi($listId, $functionCall, $param);
        }

        return $result;
    }


}
