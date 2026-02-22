$base = "http://localhost/examsystemattempt1/backend/index.php/api"

echo "1. Logging in as Student..."
$loginBody = @{
    username = "student1"
    password = "password123" 
} | ConvertTo-Json

# Register first as DB was wiped
try {
    $regBody = @{
        username = "student1"
        email = "student1@example.com"
        password = "password123"
        role = "student"
    } | ConvertTo-Json
    $reg = Invoke-RestMethod -Uri "$base/auth/register" -Method Post -Body $regBody -ContentType "application/json"
    echo "Registered Student."
} catch {
    # echo "Student likely exists."
}

$loginResponse = Invoke-RestMethod -Uri "$base/auth/login" -Method Post -Body $loginBody -ContentType "application/json"
$token = $loginResponse.token
echo "Student Token: $token"

echo "2. Submitting Mixed Exam..."
$headers = @{
    Authorization = "Bearer $token"
}

# Fetch latest exam to ensure we submit to valid one
$exams = Invoke-RestMethod -Uri "$base/exams" -Method Get -Headers $headers
$lastExam = $exams[-1]
$examId = $lastExam.id
echo "Submitting to Exam ID: $examId"

# Exam has 3 questions. We need to know their IDs...
# This is tricky without fetching the exam details first.
# Let's fetch the exam details to get Question IDs.
$examDetails = Invoke-RestMethod -Uri "$base/exams/$examId" -Method Get -Headers $headers
$q1 = $examDetails.questions[0].id
$q2 = $examDetails.questions[1].id
$q3 = $examDetails.questions[2].id

$submitBody = @{
    exam_id = $examId
    answers = @(
        @{
            question_id = $q1
            answer = "b"
        },
        @{
            question_id = $q2
            answer = "false"
        },
        @{
            question_id = $q3
            answer = "shakespeare" # testing case insensitivity
        }
    )
} | ConvertTo-Json -Depth 5

try {
    $submitResponse = Invoke-RestMethod -Uri "$base/results/submit" -Method Post -Headers $headers -Body $submitBody -ContentType "application/json"
    echo "Submission Response:"
    $submitResponse | Format-List
} catch {
    echo "Error Submitting: $_"
    $_.Exception.Response.GetResponseStream()
}

echo "3. Getting Student Results..."
$studentId = 2 # Admin is 1, Student is 2
try {
    $results = Invoke-RestMethod -Uri "$base/results/student/$studentId" -Method Get -Headers $headers
    echo "Results Count: $($results.Count)"
    $results | Format-List
} catch {
    echo "Error Getting Results: $_"
}
