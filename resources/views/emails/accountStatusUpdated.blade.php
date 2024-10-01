<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Updated</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }

        .container {
            padding: 20px;
            background-color: #fff;
            max-width: 600px;
            margin: 50px auto;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            background-color: #4CAF50;
            color: white;
            padding: 10px;
            text-align: center;
        }

        .content {
            padding: 20px;
        }

        .footer {
            text-align: center;
            margin-top: 20px;
            color: #777;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Account Status Updated</h1>
        </div>
        <div class="content">
            <p>Dear {{ $user->name }},</p>
            <p>We wanted to let you know that your account status has been updated to
                <strong>{{ $user->status }}</strong>.
            </p>
            <p>Thank you for being with us.</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} MYCAR. All rights reserved.</p>
        </div>
    </div>
</body>

</html>
