<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            margin-top: 20px;
        }

        h1 {
            color: #333;
        }

        p {
            color: #666;
            margin-bottom: 20px;
        }

        .code-panel {
            background-color: #f9f9f9;
            padding: 10px;
            border-radius: 5px;
        }

        .code {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }

        .footer {
            margin-top: 20px;
            color: #888;
        }
    </style>
</head>

<body>
    <div class="email-container">
        <h1>We have received your request to reset your account password</h1>
        <p>You can use the following code to recover your account:</p>

        <div class="code-panel">
            <div class="code">{{ $code }}</div>
        </div>

        <p>The allowed duration of the code is 5 mins from the time the message was sent</p>

        <div class="footer">
            <!-- Additional footer content if needed -->
        </div>
    </div>
</body>

</html>
