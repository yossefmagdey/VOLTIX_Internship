document.addEventListener('DOMContentLoaded', () => {
  const fullNameInput = document.getElementById('full_name') || document.getElementById('name') || document.getElementById('reg-name');
  const emailInput = document.getElementById('email') || document.getElementById('reg-email');

  // 1. جلب بيانات المستخدم الحالية
  async function loadUserData() {
    try {
      const response = await fetch('api/auth.php?action=get_user', {
        method: 'GET',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include'
      });

      if (response.status === 401 || response.status === 403) {
        window.location.href = 'auth.html';
        return;
      }

      const result = await response.json();
      if (result.success && result.user) {
        if (fullNameInput) fullNameInput.value = result.user.name || result.user.full_name || '';
        if (emailInput) emailInput.value = result.user.email || '';
      }
    } catch (error) {
      console.error('Error fetching user data:', error);
    }
  }

  // 2. جلب وتعبئة بيانات لوحة التحكم
  async function fetchAdminContent() {
    const tableBody = document.querySelector('tbody');
    if (!tableBody) return;

    try {
      const response = await fetch('api/manage_content.php', {
        method: 'GET',
        credentials: 'include'
      });

      const result = await response.json();
      tableBody.innerHTML = ''; 

      if (result.success) {
        let hasContent = false;

        if (result.services && result.services.length > 0) {
          hasContent = true;
          result.services.forEach(service => {
            tableBody.innerHTML += `
              <tr>
                <td>${service.category || 'N/A'}</td>
                <td><strong>${service.title}</strong></td>
                <td>${service.description}</td>
                <td><span class="badge bg-success">Service</span></td>
              </tr>
            `;
          });
        }

        if (result.inquiries && result.inquiries.length > 0) {
          hasContent = true;
          result.inquiries.forEach(inquiry => {
            tableBody.innerHTML += `
              <tr>
                <td>Inquiry (${inquiry.subject})</td>
                <td>${inquiry.name} (${inquiry.email})</td>
                <td>${inquiry.message}</td>
                <td><button onclick="deleteInquiry(${inquiry.id})" class="btn btn-danger btn-sm">Delete</button></td>
              </tr>
            `;
          });
        }

        if (!hasContent) {
          tableBody.innerHTML = `<tr><td colspan="4" class="text-center">No data found in database.</td></tr>`;
        }

      } else {
        tableBody.innerHTML = `<tr><td colspan="4" class="text-center text-danger">⚠️ ${result.message || 'Failed to load content.'}</td></tr>`;
      }
    } catch (error) {
      console.error('Error fetching admin data:', error);
      tableBody.innerHTML = `<tr><td colspan="4" class="text-center text-danger">❌ Error connecting to server.</td></tr>`;
    }
  }

  loadUserData();
  fetchAdminContent();
});

// 3. دالة حذف الاستفسار المعرفة عالمياً لتعمل مع زر الـ HTML
async function deleteInquiry(id) {
  if (!confirm('Are you sure you want to delete this inquiry?')) return;

  try {
    const response = await fetch('api/manage_content.php?action=delete_inquiry', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({ id: id })
    });

    const result = await response.json();
    if (result.success) {
      alert('✅ ' + result.message);
      location.reload(); // إعادة تحميل الصفحة لتحديث الجدول
    } else {
      alert('❌ ' + (result.message || 'Failed to delete.'));
    }
  } catch (error) {
    console.error('Error deleting inquiry:', error);
    alert('❌ Server connection error.');
  }
}