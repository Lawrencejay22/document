@echo off
echo ==============================================
echo   Publishing Document Management System
echo ==============================================

:: Initialize git repository if it doesn't exist
if not exist ".git" (
    echo Initializing Git repository...
    git init
)

:: Add all files
echo.
echo Adding files to Git...
git add .

:: Prompt for commit message
echo.
set /p commitMsg="Enter commit message (default: Initial commit): "
if "%commitMsg%"=="" set commitMsg=Initial commit

:: Commit changes
echo.
echo Committing changes...
git commit -m "%commitMsg%"

:: Ask for remote URL
echo.
set /p remoteUrl="Enter the GitHub repository URL (e.g., https://github.com/Lawrencejay22/Awesome-Todos.git): "

if not "%remoteUrl%"=="" (
    echo.
    echo Adding remote origin...
    git remote remove origin 2>nul
    git remote add origin %remoteUrl%
    
    echo.
    echo Pushing code to GitHub...
    git branch -M main
    git push -u origin main
) else (
    echo.
    echo No remote URL provided. Skipping push to GitHub.
)

echo.
echo Done!
pause
