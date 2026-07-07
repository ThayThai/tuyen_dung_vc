// ============================================================
//  CẤU HÌNH API
//  Dùng đường dẫn tương đối để chạy được cả localhost lẫn server thật
// ============================================================
const API = "./api.php";

// Hàm lấy token động từ localStorage (mặc định token cũ nếu chưa có)
function getAuthHeader() {
  const token = localStorage.getItem('admin_token') || "MY_SECRET_TOKEN";
  return `Bearer ${token}`;
}

// Tự động thêm header X-Authorization dự phòng nếu có Authorization (hỗ trợ cho hosting InfinityFree)
const originalFetch = window.fetch;
window.fetch = function (url, options) {
  if (options && options.headers) {
    const auth = options.headers["Authorization"] || options.headers["authorization"];
    if (auth) {
      options.headers["X-Authorization"] = auth;
    }
  }
  return originalFetch(url, options);
};

// ===== ĐỌC DỮ LIỆU =====
async function loadPL01() {
  const res = await fetch(`${API}?table=pl01_chitieu`, {
    headers: { "Authorization": getAuthHeader() }
  });
  if (!res.ok) throw new Error(`loadPL01 thất bại: ${res.status}`);
  return res.json();
}

async function loadPL02() {
  const res = await fetch(`${API}?table=pl02_chitieu`, {
    headers: { "Authorization": getAuthHeader() }
  });
  if (!res.ok) throw new Error(`loadPL02 thất bại: ${res.status}`);
  return res.json();
}

async function loadThongBao() {
  const res = await fetch(`${API}?table=thong_bao`, {
    headers: { "Authorization": getAuthHeader() }
  });
  if (!res.ok) throw new Error(`loadThongBao thất bại: ${res.status}`);
  return res.json();
}

async function loadDangKy() {
  const res = await fetch(`${API}?table=don_dang_ky`, {
    headers: { "Authorization": getAuthHeader() }
  });
  if (!res.ok) throw new Error(`loadDangKy thất bại: ${res.status}`);
  return res.json();
}

async function loadAuditLogs() {
  const res = await fetch(`${API}?table=audit_logs`, {
    headers: { "Authorization": getAuthHeader() }
  });
  if (!res.ok) throw new Error(`loadAuditLogs thất bại: ${res.status}`);
  return res.json();
}

// ===== THÊM MỚI =====
async function addRecord(table, data) {
  const res = await fetch(`${API}?table=${table}`, {
    method: "POST",
    headers: { 
      "Content-Type": "application/json", 
      "Authorization": getAuthHeader() 
    },
    body: JSON.stringify(data),
  });
  if (!res.ok) {
    const errData = await res.json().catch(() => ({}));
    throw new Error(errData.error || `addRecord thất bại: ${res.status}`);
  }
  return res.json();
}

// ===== CẬP NHẬT =====
async function updateRecord(table, id, data) {
  const res = await fetch(`${API}?table=${table}&id=${id}`, {
    method: "PUT",
    headers: { 
      "Content-Type": "application/json", 
      "Authorization": getAuthHeader() 
    },
    body: JSON.stringify(data),
  });
  if (!res.ok) {
    const errData = await res.json().catch(() => ({}));
    throw new Error(errData.error || `updateRecord thất bại: ${res.status}`);
  }
  return res.json();
}

// ===== XOÁ =====
async function deleteRecord(table, id) {
  const res = await fetch(`${API}?table=${table}&id=${id}`, {
    method: "DELETE",
    headers: { 
      "Authorization": getAuthHeader() 
    },
  });
  if (!res.ok) {
    const errData = await res.json().catch(() => ({}));
    throw new Error(errData.error || `deleteRecord thất bại: ${res.status}`);
  }
  return res.json();
}
