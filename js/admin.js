document.addEventListener('DOMContentLoaded', () => {
  const apiUrl = 'api/inquiries.php';

  const crudForm = document.getElementById('crudForm');
  const itemIdInput = document.getElementById('itemId');
  const itemNameInput = document.getElementById('itemName');
  const itemEmailInput = document.getElementById('itemEmail');
  const itemSubjectInput = document.getElementById('itemSubject');
  const itemMessageInput = document.getElementById('itemMessage');
  
  const formTitle = document.getElementById('formTitle');
  const saveBtn = document.getElementById('saveBtn');
  const cancelBtn = document.getElementById('cancelBtn');
  const itemsTableBody = document.getElementById('itemsTableBody');
  const alertBox = document.getElementById('alertBox');

  // 1. READ: Fetch all items from API with Session Support
  async function loadItems() {
    try {
      const response = await fetch(apiUrl, {
        method: 'GET',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include' // Include session cookies
      });

      if (response.status === 401 || response.status === 403) {
        showAlert('Unauthorized access. Redirecting to login...', 'error');
        setTimeout(() => { window.location.href = 'login.html'; }, 2000);
        return;
      }

      const result = await response.json();

      if (result.success && Array.isArray(result.data)) {
        renderTable(result.data);
      } else {
        itemsTableBody.innerHTML = `<tr><td colspan="7" style="text-align:center;">No items found.</td></tr>`;
      }
    } catch (error) {
      console.error('Error fetching data:', error);
      showAlert('Failed to connect to the server.', 'error');
    }
  }

  // Render items into HTML Table
  function renderTable(items) {
    if (items.length === 0) {
      itemsTableBody.innerHTML = `<tr><td colspan="7" style="text-align:center;">No items available.</td></tr>`;
      return;
    }

    itemsTableBody.innerHTML = items.map(item => `
      <tr>
        <td>${item.id}</td>
        <td>${escapeHtml(item.name || '')}</td>
        <td>${escapeHtml(item.email || '')}</td>
        <td>${escapeHtml(item.subject || '-')}</td>
        <td>${escapeHtml(item.message || '')}</td>
        <td>${item.created_at || '-'}</td>
        <td>
          <button class="btn-edit" onclick="editItem(${item.id}, '${escapeQuote(item.name || '')}', '${escapeQuote(item.email || '')}', '${escapeQuote(item.subject || '')}', '${escapeQuote(item.message || '')}')">Edit</button>
          <button class="btn-delete" onclick="deleteItem(${item.id})">Delete</button>
        </td>
      </tr>
    `).join('');
  }

  // 2. CREATE & UPDATE: Submit form
  crudForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    const id = itemIdInput.value;
    const payload = {
      id: id ? parseInt(id) : null,
      name: itemNameInput.value.trim(),
      email: itemEmailInput.value.trim(),
      subject: itemSubjectInput.value.trim(),
      message: itemMessageInput.value.trim()
    };

    const method = id ? 'PUT' : 'POST';

    try {
      const response = await fetch(apiUrl, {
        method: method,
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify(payload)
      });

      const result = await response.json();

      if (result.success) {
        showAlert(result.message, 'success');
        resetForm();
        loadItems();
      } else {
        showAlert(result.message || 'Action failed.', 'error');
      }
    } catch (error) {
      console.error('Error submitting form:', error);
      showAlert('Server error during submit.', 'error');
    }
  });

  // 3. EDIT (Prepare Form for Update)
  window.editItem = (id, name, email, subject, message) => {
    itemIdInput.value = id;
    itemNameInput.value = name;
    itemEmailInput.value = email;
    itemSubjectInput.value = subject;
    itemMessageInput.value = message;

    formTitle.textContent = `Edit Item #${id}`;
    saveBtn.textContent = 'Update Item';
    cancelBtn.style.display = 'inline-block';
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  // Cancel Edit
  if (cancelBtn) cancelBtn.addEventListener('click', resetForm);

  function resetForm() {
    itemIdInput.value = '';
    crudForm.reset();
    formTitle.textContent = 'Add New Inquiry / Item';
    saveBtn.textContent = 'Save Item';
    cancelBtn.style.display = 'none';
  }

  // 4. DELETE Item
  window.deleteItem = async (id) => {
    if (!confirm(`Are you sure you want to delete item #${id}?`)) return;

    try {
      const response = await fetch(apiUrl, {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ id: id })
      });

      const result = await response.json();

      if (result.success) {
        showAlert(result.message, 'success');
        loadItems();
      } else {
        showAlert(result.message || 'Delete failed.', 'error');
      }
    } catch (error) {
      console.error('Error deleting item:', error);
      showAlert('Failed to delete item from server.', 'error');
    }
  };

  // Helper: Show Alerts
  function showAlert(message, type) {
    if (!alertBox) return;
    alertBox.textContent = message;
    alertBox.className = `alert alert-${type === 'success' ? 'success' : 'error'}`;
    alertBox.style.display = 'block';
    setTimeout(() => {
      alertBox.style.display = 'none';
    }, 4000);
  }

  // Helpers: Sanitize Strings for HTML/Attributes
  function escapeHtml(str) {
    return String(str).replace(/[&<>"']/g, match => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[match]));
  }

  function escapeQuote(str) {
    return String(str).replace(/'/g, "\\'").replace(/"/g, '&quot;').replace(/\n/g, ' ');
  }

  // Initial Load
  loadItems();
});