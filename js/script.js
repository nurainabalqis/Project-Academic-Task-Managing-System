function updateProgressBar() {
    const total = parseInt(document.getElementById('totalTasks').textContent);
    const completed = parseInt(document.getElementById('completedTasks').textContent);
    
    if (total > 0) {
        const percentage = Math.round((completed / total) * 100);
        document.getElementById('progressBar').style.width = `${percentage}%`;
        document.getElementById('progressLabel').textContent = 
            `Progress: ${completed}/${total} tasks completed (${percentage}%)`;
    }
}

function filterTasks() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const filterType = document.getElementById('filterType').value;
    
    document.querySelectorAll('.task-row').forEach(row => {
        const title = row.dataset.title;
        const type = row.dataset.type;
        const status = row.dataset.status;
        
        const matchSearch = title.includes(search);
        const matchType = filterType === 'all' || type === filterType;
        
        if (matchSearch && matchType) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function validateTaskForm() {
    const dueDate = new Date(document.forms["taskForm"]["due_date"].value);
    const now = new Date();
    
    if (dueDate < now) {
        alert("Due date cannot be in the past!");
        return false;
    }
    return true;
}

function validateRegisterForm() {
    const pass = document.forms["registerForm"]["password"].value;
    const confirm = document.forms["registerForm"]["confirm_password"].value;
    
    if (pass !== confirm) {
        alert("Passwords do not match!");
        return false;
    }
    return true;
}

