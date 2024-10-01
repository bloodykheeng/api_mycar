<!DOCTYPE html>
<html>

<head>
    <title>Account Created</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            color: #333;
        }

        .container {
            width: 80%;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            background-color: #4CAF50;
            color: #fff;
            padding: 10px;
            text-align: center;
        }

        .content {
            margin-top: 20px;
        }

        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 12px;
            color: #aaa;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Account Created</h1>
        </div>
        <div class="content">
            <p>Hello {{ $admin->name }},</p>
            <p>A new account with the following details has been created:</p>
            <p>Name: {{ $user->name }}</p>
            <p>Email: {{ $user->email }}</p>
            <p>Role: {{ $user->role }}</p>
            <p>Status: {{ $user->status }}</p>
            <p>Please review and approve the account if necessary.</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} MYCAR. All rights reserved.</p>
        </div>
    </div>
</body>

</html>
