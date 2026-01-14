<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #043546 0%, #00434F 100%);
            color: #FFFFFF;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            text-align: center;
        }
        .error-container {
            max-width: 600px;
            padding: 40px;
        }
        h1 {
            font-size: 6rem;
            margin: 0;
            color: #E58325;
        }
        h2 {
            font-size: 2rem;
            margin: 20px 0;
        }
        p {
            font-size: 1.2rem;
            margin: 20px 0;
        }
        a {
            display: inline-block;
            margin-top: 30px;
            padding: 12px 30px;
            background-color: #E58325;
            color: #FFFFFF;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }
        a:hover {
            background-color: #00434F;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1>404</h1>
        <h2>Page Not Found</h2>
        <p>The page you're looking for doesn't exist or has been moved.</p>
        <a href="/">Go to Homepage</a>
    </div>
</body>
</html>
