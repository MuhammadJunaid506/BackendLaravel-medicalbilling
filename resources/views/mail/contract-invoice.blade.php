<!DOCTYPE html>
<html>

<head>
	<title>Contract & Invoice</title>
</head>

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
                        <h3>Contract & Invoice</h3></td>
                </tr>
                <tr>
                    <td style="padding:0 0 40px 0;">Hello <b>{{$emailData['name']}}</b>,</td>
                </tr>
                <tr>
                    <td style="padding:0 0 20px 0;">Thank you for your continue interest in Eclinic Assist. We have reviewed the information provided by you and are please for offer a service agreement based on the information provided</td>
                </tr>
                <tr>
                    <td>
                        <p>Please review and sign the service Agreement by clicking on following link below:</p>
                    </td>
                </tr>
                <tr>
                    <td><a href="{{$emailData['contract_url']}}" target="_blank">{{$emailData['contract_url']}}</a></td>
                </tr>
                <tr>
                    <td>
                        <p>Please also find the invoice for commencement of services by clicking on the following link below:</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:0 0 20px 0;"><a href="{{$emailData['invoice_url']}}" target="_blank">{{$emailData['invoice_url']}}</a></td>
                </tr>
                <tr>
                    <td style="padding:0 0 20px 0;">
                        <p>For any question or concern you may have, please fee free to contact us at 713-893-6214, or by emailing us at info@eclinicassist.com</p>
                    </td>
                </tr>
                <tr>
                    <td>
                        <p>Thanks</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </body>
</html>