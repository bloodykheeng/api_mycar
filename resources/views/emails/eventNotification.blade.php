<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Notification</title>
    <style>
        /* Add your CSS styles here */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }

        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .greeting {
            font-size: 24px;
            margin-bottom: 20px;
        }

        .message {
            font-size: 16px;
            margin-bottom: 20px;
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
        <div class="greeting">
            Hello {{ $subscriber->name ?? 'there' }},
        </div>
        <div class="message">
            {{ $message }}
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} MYCAR. All rights reserved.</p>
        </div>
    </div>
</body>

</html>
