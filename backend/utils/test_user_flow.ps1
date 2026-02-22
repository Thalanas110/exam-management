$base = "http://localhost/examsystemattempt1/backend/index.php/api"

echo "1. Logging in..."
$loginBody = @{
    username = "testuser"
    password = "password123" 
} | ConvertTo-Json

$loginResponse = Invoke-RestMethod -Uri "$base/auth/login" -Method Post -Body $loginBody -ContentType "application/json"
$token = $loginResponse.token

echo "Token received: $token"

echo "2. Getting Profile..."
$headers = @{
    Authorization = "Bearer $token"
}

$profileResponse = Invoke-RestMethod -Uri "$base/users/profile" -Method Get -Headers $headers

echo "Profile Response:"
$profileResponse | Format-List
