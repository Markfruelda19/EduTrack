/* ============================================
   EduTrack — main.js
   - AJAX live search (with photo thumbnails)
   - Delete confirmation
   - Grade color coding
   ============================================ */

document.addEventListener('DOMContentLoaded', () => {

  // ── Live Search ──────────────────────────────
  const searchInput = document.getElementById('studentSearch');
  if (searchInput) {
    let debounceTimer;
    searchInput.addEventListener('keyup', () => {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => {
        fetchStudents(searchInput.value.trim());
      }, 280);
    });
  }

  function fetchStudents(query) {
    const tbody = document.getElementById('studentTableBody');
    if (!tbody) return;

    tbody.style.opacity = '0.5';

    fetch(`/edutrack/students/search.php?q=${encodeURIComponent(query)}`)
      .then(res => res.json())
      .then(data => {
        tbody.style.opacity = '1';
        if (!data.length) {
          tbody.innerHTML = `<tr><td colspan="7" class="no-data">No students found.</td></tr>`;
          return;
        }
        tbody.innerHTML = data.map(s => {
          const initials = (s.firstname.charAt(0) + s.lastname.charAt(0)).toUpperCase();
          const thumb    = s.photo
            ? `<img src="/edutrack/assets/uploads/students/${escHtml(s.photo)}" class="student-thumb" alt="${escHtml(s.firstname)}" />`
            : `<span class="student-thumb-placeholder">${initials}</span>`;

          const avgHtml = s.average !== null
            ? `<span class="grade-number">${parseFloat(s.average).toFixed(2)}</span>
               <span class="grade-badge ${gradeBadgeClass(s.average)}">${gradeLabel(s.average)}</span>`
            : `<span style="color:var(--text-3)">—</span>`;

          return `
            <tr>
              <td style="text-align:center;padding:8px 12px;">${thumb}</td>
              <td>${escHtml(s.student_id)}</td>
              <td>${escHtml(s.lastname)}, ${escHtml(s.firstname)}</td>
              <td>${escHtml(s.course)}</td>
              <td>${escHtml(s.year_level)}</td>
              <td>${avgHtml}</td>
              <td>
                <div class="action-group">
                  <a href="/edutrack/students/profile.php?id=${s.id}" class="btn btn-ghost btn-sm">View</a>
                  <a href="/edutrack/students/edit.php?id=${s.id}" class="btn btn-warning btn-sm">Edit</a>
                  <button class="btn btn-danger btn-sm"
                    onclick="confirmDelete(${s.id}, '${escHtml(s.firstname)} ${escHtml(s.lastname)}')">Delete</button>
                </div>
              </td>
            </tr>`;
        }).join('');
      })
      .catch(() => { tbody.style.opacity = '1'; });
  }

  // ── Delete Confirmations ─────────────────────
  window.confirmDelete = (id, name) => {
    if (confirm(`Delete student "${name}"?\n\nThis will also remove all their grades.`)) {
      window.location.href = `/edutrack/students/delete.php?id=${id}`;
    }
  };

  window.confirmDeleteGrade = (id) => {
    if (confirm('Remove this grade entry?')) {
      window.location.href = `/edutrack/grades/delete_grade.php?id=${id}`;
    }
  };

  // ── Grade Helpers ────────────────────────────
  window.gradeBadgeClass = (avg) => {
    avg = parseFloat(avg);
    if (avg >= 85) return 'grade-pass';
    if (avg >= 75) return 'grade-average';
    return 'grade-fail';
  };

  window.gradeLabel = (avg) => {
    avg = parseFloat(avg);
    if (avg >= 85) return 'Excellent';
    if (avg >= 75) return 'Passing';
    return 'Failing';
  };

  // Apply grade badges to pre-rendered cells
  document.querySelectorAll('[data-grade]').forEach(el => {
    const avg = parseFloat(el.dataset.grade);
    el.classList.add(gradeBadgeClass(avg));
    el.textContent = gradeLabel(avg);
  });

  // Auto-dismiss alerts after 4 seconds
  document.querySelectorAll('.alert').forEach(el => {
    setTimeout(() => {
      el.style.transition = 'opacity .5s';
      el.style.opacity = '0';
      setTimeout(() => el.remove(), 500);
    }, 4000);
  });

  // ── Utility ──────────────────────────────────
  function escHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

});
