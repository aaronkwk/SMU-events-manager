<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Omni</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
    integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
    crossorigin="anonymous">    
    <style>
        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: "Poppins", sans-serif;
        }

        .maincontainer {
            display: flex;
            height: 100vh;
            margin: 0;
            padding: 0;
        }

        .left {
            flex: 2;
            background: rgb(20, 27, 77);
            color: goldenrod;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 3rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .left h1 {
            font-size: 4rem;
            font-weight: 700;
            margin-bottom: 1rem;
            letter-spacing: 3px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        .left p {
            font-size: 1.3rem;
            opacity: 0.95;
            margin-top: 1rem;
        }

        .right {
            flex: 1;
            background-color: rgb(20, 27, 77);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 3rem;
        }

        .signup-card {
            background: white;
            padding: 2.5rem;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }

        .signup-card h2 {
            color: black;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }
        .social-btn {
            width: 100%;
            padding: 0.85rem;
            border-radius: 50px;
            border: 1px solid #e0e0e0;
            background: white;
            font-size: 1rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            color: #333;
            margin-bottom: 1rem;
        }

        .social-btn:hover {
            background: #f8f9fa;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            color: #333;
        }

        .social-btn svg {
            width: 20px;
            height: 20px;
        }

        /* Divider */
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 1.5rem 0;
            color: #666;
            font-weight: 500;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #ddd;
        }

        .divider span {
            padding: 0 1rem;
        }

        /* Create Account Button */
        .btn-cta {
            width: 100%;
            padding: 0.85rem;
            border-radius: 50px;
            border: none;
            background: #e8e8e8;
            font-size: 1rem;
            font-weight: 600;
            color: #333;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .btn-cta:hover {
            background: #d8d8d8;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        /* Login Link */
        .login-link {
            color: rgb(20, 27, 77);
            text-decoration: none;
            font-weight: 500;
        }

        .login-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="maincontainer">
        <div class="left">
            <div class="left-content">
                <h1>OMNI</h1>
                <p class="lead">Don't miss out on any more events!</p>
            </div>
        </div>

        <div class="right">
            <div class="signup-card">
                <h2 class="text-center">Sign Up Now!</h2>     
                <button class="btn-cta">Sign Up</button>     
                <div class="divider">
                    <span>OR</span>
                </div>
                <!-- Create Account Button -->
                 <button class="btn-cta">Log In</button>  
                
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>