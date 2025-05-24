// Modal functions
function openModal(modalId) {
    document.getElementById(modalId).style.display = 'flex';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Close modal when clicking outside of it
window.onclick = function(event) {
    if (event.target.className === 'modal') {
        event.target.style.display = 'none';
    }
}

// Generate subject input fields
function generateSubjectInputs() {
    const count = parseInt(document.getElementById('subjectCount').value);
    const container = document.getElementById('subjectInputs');
    container.innerHTML = '';
    
    if (count > 20) {
        alert('Please enter a number less than or equal to 20');
        return;
    }
    
    for (let i = 1; i <= count; i++) {
        const div = document.createElement('div');
        div.className = 'form-group';
        
        const input = document.createElement('input');
        input.type = 'text';
        input.name = 'subject_names[]';
        input.placeholder = 'Subject ' + i + ' name';
        input.required = true;
        input.className = 'form-control';
        
        div.appendChild(input);
        container.appendChild(div);
    }
}

// Class modal functions
let currentClassId = null;
let currentSemesterFilter = 'all';

function openClassModal(classId) {
    currentClassId = classId;
    currentSemesterFilter = 'all';
    updateClassModal();
    openModal('classModal');
}

function updateClassModal() {
    const classInfo = classData[currentClassId];
    
    // Set title
    document.getElementById('classModalTitle').innerHTML = 
        `<i class="fas fa-graduation-cap"></i> ${classInfo.class_name} - Subjects`;
    
    // Update semester filter
    const semesterFilter = document.getElementById('semesterFilter');
    semesterFilter.innerHTML = '';
    
    // Add "All" button
    const allBtn = document.createElement('button');
    allBtn.className = `semester-btn ${currentSemesterFilter === 'all' ? 'active' : ''}`;
    allBtn.textContent = 'All Semesters';
    allBtn.onclick = () => {
        currentSemesterFilter = 'all';
        updateClassModal();
    };
    semesterFilter.appendChild(allBtn);
    
    // Add semester buttons
    for (const [semesterId, semesterData] of Object.entries(classInfo.semesters)) {
        const btn = document.createElement('button');
        btn.className = `semester-btn ${currentSemesterFilter === semesterId ? 'active' : ''}`;
        btn.textContent = semesterData.semester_name;
        btn.onclick = () => {
            currentSemesterFilter = semesterId;
            updateClassModal();
        };
        semesterFilter.appendChild(btn);
    }
    
    // Update subjects list
    const subjectsList = document.getElementById('classSubjectsList');
    subjectsList.innerHTML = '';
    
    let subjectsToShow = [];
    
    if (currentSemesterFilter === 'all') {
        // Get all subjects from all semesters
        for (const [semesterId, semesterData] of Object.entries(classInfo.semesters)) {
            subjectsToShow = subjectsToShow.concat(
                semesterData.subjects.map(subject => ({
                    name: subject,
                    semester: semesterData.semester_name,
                    semester_id: semesterId
                }))
            );
        }
    } else {
        // Get subjects for selected semester
        const semesterData = classInfo.semesters[currentSemesterFilter];
        subjectsToShow = semesterData.subjects.map(subject => ({
            name: subject,
            semester: semesterData.semester_name,
            semester_id: currentSemesterFilter
        }));
    }
    
    if (subjectsToShow.length === 0) {
        const li = document.createElement('li');
        li.className = 'no-subjects';
        li.textContent = 'No subjects found for this selection';
        subjectsList.appendChild(li);
    } else {
        subjectsToShow.forEach(subject => {
            const li = document.createElement('li');
            
            const subjectContainer = document.createElement('div');
            subjectContainer.style.display = 'flex';
            subjectContainer.style.justifyContent = 'space-between';
            subjectContainer.style.width = '100%';
            subjectContainer.style.alignItems = 'center';
            
            const subjectInfo = document.createElement('div');
            
            const subjectName = document.createElement('span');
            subjectName.textContent = subject.name;
            subjectName.style.fontWeight = '500';
            
            const semesterBadge = document.createElement('span');
            semesterBadge.textContent = subject.semester;
            semesterBadge.style.color = 'var(--primary-color)';
            semesterBadge.style.fontSize = '0.8rem';
            semesterBadge.style.marginLeft = '10px';
            
            subjectInfo.appendChild(subjectName);
            subjectInfo.appendChild(semesterBadge);
            
            const deleteBtn = document.createElement('button');
            deleteBtn.innerHTML = '<i class="fas fa-trash"></i>';
            deleteBtn.className = 'delete-btn';
            deleteBtn.onclick = (e) => {
                e.stopPropagation();
                if (confirm(`Are you sure you want to delete "${subject.name}"?`)) {
                    deleteSubject(subject.name, currentClassId, subject.semester_id);
                }
            };
            
            subjectContainer.appendChild(subjectInfo);
            subjectContainer.appendChild(deleteBtn);
            li.appendChild(subjectContainer);
            subjectsList.appendChild(li);
        });
    }
}

// Function to handle subject deletion
function deleteSubject(subjectName, classId, semesterId) {
    const formData = new FormData();
    formData.append('delete_subject', 'true');
    formData.append('subject_name', subjectName);
    formData.append('class_id', classId);
    formData.append('semester_id', semesterId);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.redirected) {
            window.location.href = response.url;
        } else {
            return response.text();
        }
    })
    .then(data => {
        if (data) {
            console.log(data);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

// Generate initial subject input fields when page loads
window.onload = function() {
    generateSubjectInputs();
};