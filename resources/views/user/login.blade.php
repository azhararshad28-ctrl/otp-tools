<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Premium Tools</title>
    <link rel="stylesheet" href="/css/premium.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card glass">
            <h1 class="auth-title">Welcome Back</h1>
            <p class="text-center text-secondary mb-4">Sign in to your premium dashboard</p>

            @if($errors->any())
                <div class="alert alert-error">
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('login') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="expert@example.com" value="{{ old('email') }}" required autofocus>
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">Access Dashboard</button>
            </form>
        </div>
    </div>
</body>
</html>
