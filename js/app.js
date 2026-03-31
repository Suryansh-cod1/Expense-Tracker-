// Form Validation
function validateForm() {
    let valid = true;
    document.getElementById('amountErr').textContent = '';
    document.getElementById('descErr').textContent = '';
    document.getElementById('dateErr').textContent = '';

    const amount = parseFloat(document.getElementById('amount').value);
    const desc = document.getElementById('description').value.trim();
    const date = document.getElementById('txDate').value;

    if (!amount || amount <= 0) {
        document.getElementById('amountErr').textContent = 'Amount must be a positive number.';
        valid = false;
    }
    if (!desc) {
        document.getElementById('descErr').textContent = 'Description is required.';
        valid = false;
    }
    if (!date) {
        document.getElementById('dateErr').textContent = 'Date is required.';
        valid = false;
    }
    return valid;
}

// Reset modal for Add
function resetModal() {
    document.getElementById('modalTitle').textContent = 'Add Transaction';
    document.getElementById('formAction').value = 'add';
    document.getElementById('txId').value = '';
    document.getElementById('amount').value = '';
    document.getElementById('description').value = '';
    document.getElementById('txDate').value = new Date().toISOString().split('T')[0];
    document.getElementById('txType').value = 'expense';
    document.getElementById('category').value = 'Food';
    document.getElementById('amountErr').textContent = '';
    document.getElementById('descErr').textContent = '';
    document.getElementById('dateErr').textContent = '';
}

// Pre-fill modal for Edit
function editModal(id, amount, desc, category, type, date) {
    document.getElementById('modalTitle').textContent = 'Edit Transaction';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('txId').value = id;
    document.getElementById('amount').value = amount;
    document.getElementById('description').value = desc;
    document.getElementById('category').value = category;
    document.getElementById('txType').value = type;
    document.getElementById('txDate').value = date;
}
