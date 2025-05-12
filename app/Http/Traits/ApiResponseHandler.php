<?php 
    namespace App\Http\Traits;
    trait ApiResponseHandler {
        /**
         * return the API success reponse
         * @author Faheem Mahar
         * @param mixed $data
         * @param string $message
         */
        public function successResponse($data,$message) {
            $responseData = [
                "message"   => $message,
                "data"      => $data,
                "status"    => 200
            ];
            return response()->json($responseData,200);
        }
        /**
         * return the API warning reponse
         * @author Faheem Mahar
         * @param mixed $data
         * @param string $message
         */
        public function warningResponse($data,$message,$code) {
            $responseData = [
                "message"   => $message,
                "data"      => $data,
                "status"    => $code
            ];
            return response()->json($responseData,$code);
        }
        /**
         * return the API error reponse
         * @author Faheem Mahar
         * @param mixed $data
         * @param string $message
         */
        public function errorResponse($data,$message,$code) {
            $responseData = [
                "message"   => $message,
                "data"      => $data,
                "status"    => $code
            ];
            return response()->json($responseData,$code);
        }
    }

?>