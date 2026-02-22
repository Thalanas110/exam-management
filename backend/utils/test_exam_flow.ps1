$base = "http://localhost/examsystemattempt1/backend/index.php/api"

echo "1. Logging in as Admin..."
$loginBody = @{
    username = "admin"
    password = "admin123" 
} | ConvertTo-Json

$loginResponse = Invoke-RestMethod -Uri "$base/auth/login" -Method Post -Body $loginBody -ContentType "application/json"
$token = $loginResponse.token
echo "Admin Token: $token"

echo "2. Creating Mixed Exam..."
$headers = @{
    Authorization = "Bearer $token"
}

$examBody = @{
    title = "Mixed Type Exam"
    description = "Testing MC, Identification, True/False"
    duration_minutes = 45
    questions = @(
        @{
            question_text = "What is 2+2?"
            question_type = "multiple_choice"
            options = @{
                a = "3"
                b = "4"
                c = "5"
                d = "6"
            }
            correct_option = "b"
        },
        @{
            question_text = "Is the earth flat?"
            question_type = "true_false"
            options = @{
                true = "True"
                false = "False"
            }
            correct_option = "false"
        },
        @{
            question_text = "Who wrote Hamlet?"
            question_type = "identification"
            options = $null
            correct_option = "Shakespeare"
        }
    )
} | ConvertTo-Json -Depth 10

try {
    $createResponse = Invoke-RestMethod -Uri "$base/exams" -Method Post -Headers $headers -Body $examBody -ContentType "application/json"
    echo "Full Create Response:"
    $createResponse | ConvertTo-Json -Depth 5
    echo "Exam Created: ID $($createResponse.id)"
} catch {
    echo "Error Creating Exam: $_"
    $_.Exception.Response.GetResponseStream()
}

echo "3. Getting Exam Details..."
try {
    $examId = $createResponse.id
    if (-not $examId) { $examId = 1 } # Fallback if creation failed
    $exam = Invoke-RestMethod -Uri "$base/exams/$examId" -Method Get
    echo "Exam Questions for ID $examId :"
    $exam.questions | ForEach-Object {
        echo "Type: $($_.question_type)"
        echo "Text: $($_.question_text)"
        echo "Options: $($_.options | ConvertTo-Json -Compress)"
        echo "---"
    }
} catch {
    echo "Error Getting Exam: $_"
}
