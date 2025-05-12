<!DOCTYPE html>
<html>

<head>
	<title>Business Assessment Form</title>
</head>
<style>
table th,
td {
	border: 2px solid #000;
}

table th {
	padding: 10px;
}
</style>
@php
    try {
        $begningDate = explode("-",$mailData["begining_date"]);
        $begningDateFormat = $begningDate[1]."/". $begningDate[2]."/".$begningDate[0];
    }
    catch (\Throwable $exception) {
          $begningDateFormat = $mailData["begining_date"];
    }
@endphp
<body>
	<h2>BAF Summary</h2> </tr>
	<tr>
		<td style="padding:0 0 20px 0;">Dear <b>{{$mailData["contact_person_name"]}},</b> </td>
	</tr>
	<tr>
		<td style="padding:0 0 20px 0;">
			<p>Thank you for completing the business Assessment form at eclinicassist.com </p>
		</td>
	</tr>
	<tr>
		<td>
			<p>Here is a summary of submitted information:</p>
		</td>
	</tr>
    @if($mailData["provider_type"] == "solo" && $mailData["business_type"] == "startup")
	<table style="width:100%">
		
		<thead>
			<th>Pratice Information</th>
		</thead>
		<tr>
			<td>
				<ul>
					<li>Provider Type : <b>{{$mailData["provider_type"]}} </b></li>
					<li>Bussiness Type : <b>{{$mailData["business_type"]}} </b></li>
					<li>When do you plan on begining to see patient: <b>{{$begningDateFormat}}</b></li>
					<li>Contact person name: <b>{{$mailData["contact_person_name"]}}</b></li>
					<li>Contact person addess: <b>{{$mailData["address"]}} {{$mailData["address_line_one"]}} </b></li>
				</ul>
			</td>
		</tr>
	</table>
    @endif
    @if($mailData["provider_type"] == "solo" && $mailData["business_type"] == "established")
	<table style="width:100%">
		
		<thead>
			<th>Pratice Information</th>
		</thead>
		<tr>
			<td>
				<ul>
					<li>Provider Type : <b>{{$mailData["provider_type"]}} </b></li>
					<li>Bussiness Type : <b>{{$mailData["business_type"]}}</b> </li>
					<li>No of physical locations : <b>{{$mailData["num_of_physical_locations"]}}</b></li>
                    <li>Average patient flow per day : <b>{{$mailData["avg_pateints_day"]}}</b></li>
					<li>PMS currently in use : <b>{{$mailData["practice_manage_software_name"]}}</b></li>
					<li>Do you wish to continue using the software : <b>{{$mailData["use_pms"]}}</b></li>
                    <li>EHR currently in use : <b>{{$mailData["electronic_health_record_software"]}}</b></li>
					<li>Do you wish to continue using the software : <b>{{$mailData["use_ehr"]}}</b></li>
					<li>Contact person name: <b>{{$mailData["contact_person_name"]}}</b></li>
					<li>Contact person addess: <b>{{$mailData["address"]}} {{$mailData["address_line_one"]}} </b></li>
				</ul>
			</td>
		</tr>
	</table>
    @endif

    @if($mailData["provider_type"] == "group" && $mailData["business_type"] == "startup")
	<table style="width:100%">
		
		<thead>
			<th>Pratice Information</th>
		</thead>
		<tr>
			<td>
				<ul>
					<li>Provider Type : <b>{{$mailData["provider_type"]}}</b> </li>
					<li>Bussiness Type : <b>{{$mailData["business_type"]}}</b> </li>
					<li>When do you plan on begining to see patient: <b>{{$begningDateFormat}}</b></li>
					<li>Contact person name: <b>{{$mailData["contact_person_name"]}}</b></li>
					<li>Contact person addess: <b>{{$mailData["address"]}} {{$mailData["address_line_one"]}} </b></li>
				</ul>
			</td>
		</tr>
	</table>
    @endif
    @if($mailData["provider_type"] == "group" && $mailData["business_type"] == "established")
	<table style="width:100%">
		
		<thead>
			<th>Pratice Information</th>
		</thead>
		<tr>
			<td>
				<ul>
					<li>Provider Type : <b>{{$mailData["provider_type"]}}</b> </li>
					<li>Bussiness Type : <b>{{$mailData["business_type"]}} </b></li>
					<li>Legal Business Name : <b>{{$mailData["legal_business_name"]}}</b></li>
					<li>Doing bussiness as : <b>{{$mailData["business_as"]}}</b></li>
					<li>No of physical locations : <b>{{$mailData["num_of_physical_locations"]}}</b></li>
					<li>PMS currently in use : <b>{{$mailData["practice_manage_software_name"]}}</b></li>
					<li>Do you wish to continue using the software : <b>{{$mailData["use_pms"]}}</b></li>
                    <li>EHR currently in use : <b>{{$mailData["electronic_health_record_software"]}}</b></li>
					<li>Do you wish to continue using the software : <b>{{$mailData["use_ehr"]}}</b></li>
					<li>Number of individual health providers :  <b>{{$mailData["num_of_provider"]}}</b></li>
					<li>Contact person name: <b>{{$mailData["contact_person_name"]}}</b></li>
					<li>Contact person addess: <b>{{$mailData["address"]}} {{$mailData["address_line_one"]}} </b></li>
				</ul>
			</td>
		</tr>
	</table>
    @endif
	<tr>
		<td>
			<p>For any questions or concerns, please feel free to reach out to us at info@eclinicassist.com, or at 713-893-6214.</p>
		</td>
	</tr>
	<tr>
		<td>
			<p>Thank you. </p>
		</td>
	</tr>
</body>

</html>