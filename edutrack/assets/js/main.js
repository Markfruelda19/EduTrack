/* ============================================
   EduTrack — main.js
   - AJAX live search
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
        const query = searchInput.value.trim();
        fetchStudents(query);
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
        tbody.innerHTML = data.map(s => `
          <tr>
            <td>${escHtml(s.student_id)}</td>
            <td>${escHtml(s.lastname)}, ${escHtml(s.firstname)}</td>
            <td>${escHtml(s.course)}</td>
            <td>${escHtml(s.year_level)}</td>
            <td>
              ${s.average !== null
                ? `<span class="grade-number">${parseFloat(s.average).toFixed(2)}</span>
                   <span class="grade-badge ${gradeBadgeClass(s.average)}">${gradeLabel(s.average)}</span>`
                : '<span style="color:var(--text-3)">—</span>'}
            </td>
            <td class="action-group">
              <a href="/edutrack/students/profile.php?id=${s.id}" class="btn btn-ghost btn-sm">View</a>
              <a href="/edutrack/students/edit.php?id=${s.id}" class="btn btn-warning btn-sm">Edit</a>
              <button class="btn btn-danger btn-sm" onclick="confirmDelete(${s.id}, '${escHtml(s.firstname)} ${escHtml(s.lastname)}')">Delete</button>
            </td>
          </tr>
        `).join('');
      })
      .catch(() => { tbody.style.opacity = '1'; });
  }

  // ── Delete Confirmation ──────────────────────
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

  // Apply grade badges to any pre-rendered grade cells
  document.querySelectorAll('[data-grade]').forEach(el => {
    const avg = parseFloat(el.dataset.grade);
    el.classList.add(gradeBadgeClass(avg));
    el.textContent = gradeLabel(avg);
  });

  // ── Utility ──────────────────────────────────
  function escHtml(str) {
    return String(str)
      .replace(/&/g,'&amp;')
      .replace(/</g,'&lt;')
      .replace(/>/g,'&gt;')
      .replace(/"/g,'&quot;');
  }

  // Auto-dismiss alerts after 4 seconds
  document.querySelectorAll('.alert').forEach(el => {
    setTimeout(() => {
      el.style.transition = 'opacity .5s';
      el.style.opacity = '0';
      setTimeout(() => el.remove(), 500);
    }, 4000);
  });

});