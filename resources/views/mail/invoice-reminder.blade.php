<!DOCTYPE html>
<html>

<head>
	<title>Invoice Reminder email</title>
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

<body>
	<table>
		<thead>
			<th></th>
			<th></th>
			<th></th>
		</thead>
		<tbody>
			<tr>
				<td>
					<h3>Invoice Reminder For The Month of {{$emailData["month"]}} {{$emailData['year']}}</h3>
				</td>
			</tr>
			<tr>
				<td style="padding:0 0 40px 0;">Hello, {{$emailData['name']}}</td>
			</tr>
			<tr>
				<td style="padding:0 0 20px 0;">I hope you're well. This is a friendly reminder that invoice #{{$emailData["invoice_number"]}} for services acquired was due on {{$emailData['sending_date']}} please access the due invoice by clicking on 
				<a href="{{$emailData['invoice_url']}}" target="_blank">{{$emailData['invoice_url']}}</a></td>
			</tr>
			<tr>
				<td style="padding:0 0 20px 0;">
					<p>Please pay the invoice at the earliest to prevent disruption of services</p>
				</td>
			</tr>
			<tr>
				<td style="padding:0 0 20px 0;">
					<p>please pay the invoice at the earliest to prevent disruption of services. For any questions or concern you may have, please feel free to contact us at 713-893-6214, or by emailing us at info@eclinicassist.com</p>
				</td>
			</tr>
			<tr>
				<td>
					<p>Thank you for your business</p>
				</td>
			</tr>
		</tbody>
	</table>
</body>

</html>