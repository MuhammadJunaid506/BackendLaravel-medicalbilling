<!DOCTYPE html>
<html>
<head>
    <title>W9Form</title>
</head>
<body>
    <?php
    $w9FormPage1 = public_path('certificate_template/saved/'.$page1.'.jpg');
    ?>
    <div style="text-align: center;">
        <img src="{{ $w9FormPage1 }}" style="width: 100%; height: 100%">
        <!-- <img src="{{ public_path('certificate_template/fw9-2.jpg') }}" style="width: 100%; height: 100%">
        <img src="{{ public_path('certificate_template/fw9-3.jpg') }}" style="width: 100%; height: 100%">
        <img src="{{ public_path('certificate_template/fw9-4.jpg') }}" style="width: 100%; height: 100%">
        <img src="{{ public_path('certificate_template/fw9-5.jpg') }}" style="width: 100%; height: 100%"> -->
    </div>
</body>
</html>