<?php 
    namespace App\Http\Traits;
    use DB;
    use PDF;
    use Carbon\Carbon;
    trait EditImage {
        
        private $width = "100%";
        private $height = "960";
        /**
         * edit the image
         * 
         * @param $sourceFile
         * @param $name
         * @param $business
         * @param $federalTaxClassification
         * @param $examptPayCode
         * @param $fatcaCode
         * @param $address
         * @param $city
         * @param $requestorName
         * @param $requestorAddress
         * @param $socialSecurityNumber
         * @param $employeeIdentificationNum
         * @param $date
         * @param $llc
         * @param $othersInfo
         * @param $accountNumber
         * @return boolean
         */
        public function addTextOnImage(
            $sourceFile,$name,$business,$federalTaxClassification,
            $examptPayCode,$fatcaCode,$address,$cityStateZip,$requestorName,
            $requestorAddress,$socialSecurityNumber,$employeeIdentificationNum,
            $date,$llc,$othersText,$othersInfo,$accountNumber,$saveFileAs,$signature=""
        ) {
        //     echo strlen($othersInfo);
        //    //print_r(func_get_args());
        //    exit;
            $image = imagecreatefromjpeg($sourceFile);
            $black = imagecolorallocate($image, 0, 0, 0);
            // Allocate A Color For The Text
            $gray = imagecolorallocate($image, 00, 000, 00);
           
            // Set Path to Font File
            $firstFont = public_path('fonts/calibriregular.ttf');
            
            $checkboxImage = \imagecreatefromstring(file_get_contents(public_path('icon/checkkk.png'))); 

            // Print Text On Image
            imagettftext($image, 20, 0, 190,280, $gray, $firstFont, $name);
            imagettftext($image, 20, 0, 190, 350, $gray, $firstFont, $business);
            if($llc !=".")
                imagettftext($image, 20, 0, 1150, 520, $gray, $firstFont, $llc);

            if(strpos($federalTaxClassification, "I’m not sure") !== false) {
                imagettftext($image, 20, 0,450, 660, $gray, $firstFont, $othersInfo);
                imagecopymerge($image, $checkboxImage, 185, 643, 0, 0, 16, 16, 100); 
            }

            if(strpos($federalTaxClassification, "Individual/sole proprietor or single-member LLC") !== false)
                imagecopymerge($image, $checkboxImage, 185, 447, 0, 0, 16, 16, 100);
            if(strpos($federalTaxClassification, "C Corporation") !== false)
                imagecopymerge($image, $checkboxImage, 503, 442, 0, 0, 16, 16, 100);
            if(strpos($federalTaxClassification , "S Corporation") !==false)    
                imagecopymerge($image, $checkboxImage, 703, 442, 0, 0, 16, 16, 100);
            if(strpos($federalTaxClassification,"Partnership") !==false)    
                imagecopymerge($image, $checkboxImage, 902, 442, 0, 0, 16, 16, 100);
            if(strpos($federalTaxClassification,"Trust/estate") !==false)
                imagecopymerge($image, $checkboxImage, 1104, 442, 0, 0, 16, 16, 100);
            if(strpos($federalTaxClassification,"Limited Liability Company") !==false)
                imagecopymerge($image, $checkboxImage, 185, 513, 0, 0, 16, 16, 100);  
           

            if($examptPayCode !="")
                imagettftext($image,20, 0, 1508,490, $gray, $firstFont, $examptPayCode);

            if($fatcaCode !="")
                imagettftext($image,20, 0, 1400,590, $gray, $firstFont, $fatcaCode); 

            imagettftext($image,20, 0, 190,715, $gray, $firstFont, $address);
            imagettftext($image,20, 0, 190,785, $gray, $firstFont, $cityStateZip);
            
            if($accountNumber !="")
                imagettftext($image,20, 0, 190,850, $gray, $firstFont, $accountNumber);
            if($requestorName !="")
                imagettftext($image,20, 0, 1110,713, $gray, $firstFont, $requestorName);
            if($requestorAddress !="")
            imagettftext($image,20, 0, 1110,753, $gray, $firstFont, $requestorAddress);

           
            if($socialSecurityNumber !="") {
                imagettftext($image,20, 0, 1173,975, $gray, $firstFont, $socialSecurityNumber[0]);
                imagettftext($image,20, 0, 1213,975, $gray, $firstFont, $socialSecurityNumber[1]);
                imagettftext($image,20, 0, 1253,975, $gray, $firstFont, $socialSecurityNumber[2]);
                imagettftext($image,20, 0, 1332,975, $gray, $firstFont, $socialSecurityNumber[3]);
                imagettftext($image,20, 0,  1372,975, $gray, $firstFont, $socialSecurityNumber[4]);
                imagettftext($image,20, 0, 1452,975, $gray, $firstFont, $socialSecurityNumber[5]);
                imagettftext($image,20, 0, 1492,975, $gray, $firstFont, $socialSecurityNumber[6]);
                imagettftext($image,20, 0,1532,975, $gray, $firstFont, $socialSecurityNumber[7]);
                imagettftext($image,20, 0, 1572,975, $gray, $firstFont, $socialSecurityNumber[8]);
            }

            if($employeeIdentificationNum !="") {
                imagettftext($image,20, 0, 1173,1105, $gray, $firstFont, $employeeIdentificationNum[0]);
                imagettftext($image,20, 0, 1213,1105, $gray, $firstFont, $employeeIdentificationNum[1]);
                imagettftext($image,20, 0, 1293,1105, $gray, $firstFont, $employeeIdentificationNum[2]);
                imagettftext($image,20, 0, 1332,1105, $gray, $firstFont, $employeeIdentificationNum[3]);
                imagettftext($image,20, 0,  1372,1105, $gray, $firstFont, $employeeIdentificationNum[4]);
                imagettftext($image,20, 0, 1412,1105, $gray, $firstFont, $employeeIdentificationNum[5]); 
                imagettftext($image,20, 0, 1452,1105, $gray, $firstFont, $employeeIdentificationNum[6]);
                imagettftext($image,20, 0, 1492,1105, $gray, $firstFont, $employeeIdentificationNum[7]);
                imagettftext($image,20, 0,1532,1105, $gray, $firstFont, $employeeIdentificationNum[8]); 
            
            }
            
            imagettftext($image,18, 0,1173,1555, $gray, $firstFont, $date);

            imagettftext($image,18, 0,380,1555, $gray, $firstFont, $signature);
            
            $folder = public_path('certificate_template/saved');
            
            if (!is_dir($folder)) {
                mkdir($folder, 0777, true);
            }
            //create the new edited image
            imagejpeg($image, public_path('certificate_template/saved/'.$saveFileAs.'-w9-1.jpg'));    
        
            // Clear Memory
            imagedestroy($image);

            return true;
        }
        /**
         * create the practice w9 form
         * 
         * @param $sourceFile
         * @param $practiceName
         * @param $dBA
         * @param $classification
         * @param $ownerShipStatus
         * @param $address
         * @param $cityStateZip
         * @param $taxId
         * @param $practiceId
         * @return boolean
         */
        public function editW9PracticeImage(
            $sourceFile,$practiceName,$dBA,$classification,
            $ownerShipStatus,$address,$cityStateZip,
            $taxId,$practiceId
        ) {
            $image = \imagecreatefromjpeg($sourceFile);
            $black = \imagecolorallocate($image, 0, 0, 0);
            // Allocate A Color For The Text
            $gray = \imagecolorallocate($image, 00, 000, 00);
           
            // Set Path to Font File
            $firstFont = public_path('fonts/calibriregular.ttf');
            
            $checkboxImage = \imagecreatefromstring(file_get_contents(public_path('icon/checkkk.png'))); 

            // Print Text On Image
            \imagettftext($image, 20, 0, 190,280, $gray, $firstFont, $practiceName);
            \imagettftext($image, 20, 0, 190, 350, $gray, $firstFont, $dBA);
            if($classification !="." && $ownerShipStatus=="Limited Liability Company")
                \imagettftext($image, 20, 0, 1150, 520, $gray, $firstFont, $classification);

            if(strpos($ownerShipStatus, "Sole Proprietorship") !== false)
                \imagecopymerge($image, $checkboxImage, 185, 447, 0, 0, 16, 16, 100);
            if(strpos($ownerShipStatus, "C - Corporation") !== false)
                \imagecopymerge($image, $checkboxImage, 503, 442, 0, 0, 16, 16, 100);
            if(strpos($ownerShipStatus , "S - Corporation") !==false)    
                \imagecopymerge($image, $checkboxImage, 703, 442, 0, 0, 16, 16, 100);
            if(strpos($ownerShipStatus,"Partnership") !==false)    
                \imagecopymerge($image, $checkboxImage, 902, 442, 0, 0, 16, 16, 100);
            if(strpos($ownerShipStatus,"Trust/estate") !==false)
                \imagecopymerge($image, $checkboxImage, 1104, 442, 0, 0, 16, 16, 100);
            if(strpos($ownerShipStatus,"Limited Liability Company") !==false)
                \imagecopymerge($image, $checkboxImage, 185, 513, 0, 0, 16, 16, 100);  

            \imagettftext($image,20, 0, 190,715, $gray, $firstFont, $address);
            \imagettftext($image,20, 0, 190,785, $gray, $firstFont, $cityStateZip);

            if($taxId !="") {
                \imagettftext($image,20, 0, 1173,1105, $gray, $firstFont, $taxId[0]);
                \imagettftext($image,20, 0, 1213,1105, $gray, $firstFont, $taxId[1]);
                \imagettftext($image,20, 0, 1293,1105, $gray, $firstFont, $taxId[2]);
                \imagettftext($image,20, 0, 1332,1105, $gray, $firstFont, $taxId[3]);
                \imagettftext($image,20, 0,  1372,1105, $gray, $firstFont, $taxId[4]);
                \imagettftext($image,20, 0, 1412,1105, $gray, $firstFont, $taxId[5]); 
                \imagettftext($image,20, 0, 1452,1105, $gray, $firstFont, $taxId[6]);
                \imagettftext($image,20, 0, 1492,1105, $gray, $firstFont, $taxId[7]);
                \imagettftext($image,20, 0,1532,1105, $gray, $firstFont, $taxId[8]); 
            
            }
            $date = Carbon::now()->format('m/d/y');
            imagettftext($image,18, 0,1173,1555, $gray, $firstFont, $date);
            $folder = public_path('certificate_template/saved');
            
            if (!is_dir($folder)) {
                mkdir($folder, 0777, true);
            }
            //create the new edited image
            \imagejpeg($image, public_path('certificate_template/saved/'.$practiceId.'-w9-1.jpg'));    
        
            // Clear Memory
            \imagedestroy($image);

            return true;
        }
         /**
         * create the practice w9 form
         * 
         * @param $sourceFile
         * @param $providerName
         * @param $ownerShipStatus
         * @param $address
         * @param $cityStateZip
         * @param $ssn
         * @param $providerId
         * @return boolean
         */
        public function editW9ProviderImage(
            $sourceFile,$providerName,
            $address,$cityStateZip,
            $ssn,$providerId
        ) {

            $image = \imagecreatefromjpeg($sourceFile);
            $black = \imagecolorallocate($image, 0, 0, 0);
            // Allocate A Color For The Text
            $gray = \imagecolorallocate($image, 00, 000, 00);
           
            // Set Path to Font File
            $firstFont = public_path('fonts/calibriregular.ttf');
            
            $checkboxImage = \imagecreatefromstring(file_get_contents(public_path('icon/checkkk.png'))); 

            // Print Text On Image
            \imagettftext($image, 20, 0, 190,280, $gray, $firstFont, $providerName);

            \imagecopymerge($image, $checkboxImage, 185, 447, 0, 0, 16, 16, 100);

            \imagettftext($image,20, 0, 190,715, $gray, $firstFont, $address);
            \imagettftext($image,20, 0, 190,785, $gray, $firstFont, $cityStateZip);
            // echo $address;
            // exit;
            if($ssn !="") {
                imagettftext($image,20, 0, 1173,975, $gray, $firstFont, $ssn[0]);
                imagettftext($image,20, 0, 1213,975, $gray, $firstFont, $ssn[1]);
                imagettftext($image,20, 0, 1253,975, $gray, $firstFont, $ssn[2]);
                imagettftext($image,20, 0, 1332,975, $gray, $firstFont, $ssn[3]);
                imagettftext($image,20, 0,  1372,975, $gray, $firstFont, $ssn[4]);
                imagettftext($image,20, 0, 1452,975, $gray, $firstFont, $ssn[5]);
                imagettftext($image,20, 0, 1492,975, $gray, $firstFont, $ssn[6]);
                imagettftext($image,20, 0,1532,975, $gray, $firstFont, $ssn[7]);
                imagettftext($image,20, 0, 1572,975, $gray, $firstFont, $ssn[8]);
            }
            $date = Carbon::now()->format('m/d/y');
            imagettftext($image,18, 0,1173,1555, $gray, $firstFont, $date);
            $folder = public_path('certificate_template/saved');
            
            if (!is_dir($folder)) {
                mkdir($folder, 0777, true);
            }
            //create the new edited image
            \imagejpeg($image, public_path('certificate_template/saved/'.$providerId.'-w9-1.jpg'));    
        
            // Clear Memory
            \imagedestroy($image);

            return true;
        }
        /**
         * save w9Form pdf file
         * 
         * @param $fileName
         * @return void
         */
        public function saveW9FormAsPdf($fileName) {
            
            set_time_limit(0);
            
            $html = view('pdf.w9form',["page1" => $fileName]);
            
            $folder = public_path('w9form');
            
            if (!is_dir($folder)) {
                mkdir($folder, 0777, true);
            }
            $pdfPath = public_path("w9form/".$fileName.".pdf");
            
            return Pdf::loadHTML($html)->setPaper('a4', 'portrait')->setWarnings(false)->save($pdfPath);
        }
    }
?>    