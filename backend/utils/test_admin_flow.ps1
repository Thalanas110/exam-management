$base = "http://localhost/examsystemattempt1/backend/index.php/api"

echo "1. Logging in as Admin..."
$loginBody = @{
    username = "admin"
    password = "admin123" 
} | ConvertTo-Json

try {
    $loginResponse = Invoke-RestMethod -Uri "$base/auth/login" -Method Post -Body $loginBody -ContentType "application/json"
    $token = $loginResponse.token
    echo "Admin Token Received."
} catch {
    echo "Login Failed: $_"
    exit
}

$headers = @{
    Authorization = "Bearer $token"
}

echo "2. Testing GET /api/admin/exams"
try {
    $exams = Invoke-RestMethod -Uri "$base/admin/exams" -Method Get -Headers $headers
    echo "Exams Count: $($exams.Count)"
    $exams | Select-Object id, title | Format-Table
} catch {
    echo "Error: $_"
    $_.Exception.Response.GetResponseStream()
}

echo "3. Testing GET /api/admin/results"
try {
    $results = Invoke-RestMethod -Uri "$base/admin/results" -Method Get -Headers $headers
    echo "Results Count: $($results.Count)"
    $results | Format-Table
} catch {
    echo "Error: $_"
}

echo "4. Testing GET /api/reports/exam-performance"
try {
    $perf = Invoke-RestMethod -Uri "$base/reports/exam-performance" -Method Get -Headers $headers
    $perf | Format-Table
} catch {
    echo "Error: $_"
}

echo "5. Testing GET /api/reports/pass-fail"
try {
    $pf = Invoke-RestMethod -Uri "$base/reports/pass-fail" -Method Get -Headers $headers
    $pf | Format-Table
} catch {
    echo "Error: $_"
}
