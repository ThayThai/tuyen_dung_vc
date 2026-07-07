// ============================================================
//  CẤU HÌNH THỂ HIỆN TRANG WEB TUYỂN DỤNG
//  Tỉnh thành khác khi triển khai chỉ cần chỉnh sửa file này
// ============================================================
const CONFIG = {
  provinceName: "Long An",
  deptName: "Sở GDĐT tỉnh Long An",
  fullDeptName: "Sở Giáo dục và Đào tạo tỉnh Long An",
  year: "2026",
  planNumber: "Kế hoạch 123/KH-SGDĐT ngày 15/5/2026",
  totalQuotas: "850",
  totalSchools: "320+",
  address: "Tp. Tân An, Long An",
  contactPhone: "0272.3826.xxx",
  emailDomain: "longan.edu.vn",
  googleMapsQuerySuffix: "Tỉnh Long An"
};

// Hàm tự động quét và thay thế văn bản hardcoded bằng cấu hình
function applyConfigToDOM() {
  // Thay thế tiêu đề trang
  document.title = document.title
    .replaceAll("Sở GDĐT Đồng Tháp", CONFIG.deptName)
    .replaceAll("Đồng Tháp", CONFIG.provinceName)
    .replaceAll("2026", CONFIG.year);

  // Quét toàn bộ DOM để thay thế văn bản hiển thị
  function replaceTextNodes(node) {
    if (node.nodeType === Node.TEXT_NODE) {
      let text = node.nodeValue;
      let modified = false;

      if (text.includes("Sở GDĐT tỉnh Đồng Tháp")) {
        text = text.replaceAll("Sở GDĐT tỉnh Đồng Tháp", CONFIG.deptName);
        modified = true;
      }
      if (text.includes("Sở Giáo dục và Đào tạo tỉnh Đồng Tháp")) {
        text = text.replaceAll("Sở Giáo dục và Đào tạo tỉnh Đồng Tháp", CONFIG.fullDeptName);
        modified = true;
      }
      if (text.includes("Sở GDĐT Đồng Tháp")) {
        text = text.replaceAll("Sở GDĐT Đồng Tháp", CONFIG.deptName);
        modified = true;
      }
      if (text.includes("Kế hoạch 407/KH-SGDĐT ngày 24/4/2026")) {
        text = text.replaceAll("Kế hoạch 407/KH-SGDĐT ngày 24/4/2026", CONFIG.planNumber);
        modified = true;
      }
      if (text.includes("Tp. Cao Lãnh, Đồng Tháp")) {
        text = text.replaceAll("Tp. Cao Lãnh, Đồng Tháp", CONFIG.address);
        modified = true;
      }
      if (text.includes("1.240 chỉ tiêu")) {
        text = text.replaceAll("1.240 chỉ tiêu", CONFIG.totalQuotas + " chỉ tiêu");
        modified = true;
      }
      if (text.includes("468+ đơn vị")) {
        text = text.replaceAll("468+ đơn vị", CONFIG.totalSchools + " đơn vị");
        modified = true;
      }
      if (text.includes("Đồng Tháp")) {
        text = text.replaceAll("Đồng Tháp", CONFIG.provinceName);
        modified = true;
      }
      if (modified) {
        node.nodeValue = text;
      }
    } else {
      if (node.nodeName !== 'SCRIPT' && node.nodeName !== 'STYLE') {
        node.childNodes.forEach(replaceTextNodes);
      }
    }
  }

  replaceTextNodes(document.body);

  // Thay thế các thuộc tính động như Google Maps query
  document.querySelectorAll("a").forEach(a => {
    if (a.href && a.href.includes("query=")) {
      a.href = a.href.replaceAll("Tỉnh Đồng Tháp", CONFIG.googleMapsQuerySuffix);
    }
  });
}

// Chạy tự động khi trang tải xong DOM
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", applyConfigToDOM);
} else {
  applyConfigToDOM();
}
