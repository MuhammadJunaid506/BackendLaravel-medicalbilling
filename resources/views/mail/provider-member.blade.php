<!DOCTYPE html>
<html>

<head>
    <title>Provider information Form</title>
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
                    <h3>Provider information Form</h3>
                </td>
            </tr>
            <tr>
                <td style="padding:0 0 20px 0;">Hello,</td>
            </tr>
            <tr>
                <td style="padding:0 0 20px 0;">
                    <p>We have sent you a from link and form attachment link, these information we are required from your side to proceed your service request, so kindly fill out information and send back to us </p>
                </td>
            </tr>
            <tr>
                <td>
                    <p>Please access link form and attachment mentioned below:</p>
                </td>
            </tr>
            <tr>
                <td>
                    <p>Information & Attachment Form link : <a href="{{$mailData['link']}}" target="_blank">{{$mailData['link']}}</a></p>
                </td>
            </tr>
           
            <tr>
                <td style="padding:0 0 20px 0;">
                    <p>If you have any question or concern regarding same, feel free to contact us on below email at info@eclinicassist.com</p>
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