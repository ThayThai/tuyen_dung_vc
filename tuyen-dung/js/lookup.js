// ============================================================
// TRA CỨU KẾT QUẢ TUYỂN DỤNG TRỰC TIẾP TỪ DATABASE MYSQL (CÓ OTP)
// ============================================================

let currentCccd = '';
let currentVong = '';
let currentHoten = '';
let currentDonvi = '';
let resendCooldown = 0;
let cooldownInterval = null;

// Hàm chuẩn hóa tên trường để so khớp mềm dẻo (bỏ dấu, bỏ ngoặc đơn, chuẩn hóa khoảng trắng)
function cleanSchoolName(str) {
  if (!str) return '';
  return str
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .replace(/[đĐ]/g, "d")
    .replace(/\s*\(.*?\)\s*/g, ' ')
    .replace(/\s+/g, ' ')
    .trim()
    .toLowerCase();
}

async function lookup(vong) {
  const cccd  = document.getElementById('cccd-'  + vong).value.trim();
  const hoten = document.getElementById('hoten-' + vong).value.trim();
  const donvi = document.getElementById('donvi-' + vong).value.trim();
  const el    = document.getElementById('result-' + vong);

  if (!cccd) {
    el.innerHTML = '<div class="res-card res-fail"><div class="res-icon">⚠️</div><div class="res-txt"><h4>Số CCCD hoặc Số điện thoại là bắt buộc</h4><p>Vui lòng nhập số CCCD hoặc Số điện thoại để hệ thống gửi mã xác thực OTP bảo mật.</p></div></div>';
    return;
  }
  el.innerHTML = '<div class="res-card res-pending"><div class="res-icon">⏳</div><div class="res-txt"><h4>Đang tra cứu từ hệ thống...</h4><p>Vui lòng chờ trong giây lát.</p></div></div>';

  currentCccd = cccd;
  currentVong = vong;
  currentHoten = hoten;
  currentDonvi = donvi;

  const otpToken = sessionStorage.getItem('otp_token_' + cccd) || '';

  try {
    const url = `./api.php?table=don_dang_ky&cccd=${encodeURIComponent(cccd)}&otp_token=${encodeURIComponent(otpToken)}`;
    const res = await fetch(url);
    
    if (res.status === 403) {
      triggerOtpFlow(cccd);
      return;
    }
    
    if (!res.ok) {
      const errData = await res.json();
      throw new Error(errData.error || 'Yêu cầu tra cứu thất bại.');
    }
    
    const found = await res.json();
    renderResults(found, el, vong, donvi);
  } catch(e) {
    console.error(e);
    el.innerHTML = `<div class="res-card res-fail"><div class="res-icon">❌</div><div class="res-txt"><h4>Lỗi tra cứu</h4><p>${e.message}</p></div></div>`;
  }
}

function triggerOtpFlow(cccd) {
  const modal = document.getElementById('otp-modal');
  const desc = document.getElementById('otp-modal-desc');
  const otpInput = document.getElementById('otp-input');
  otpInput.value = '';
  
  desc.innerHTML = '<span style="color: var(--bm);">⏳ Đang gửi mã OTP đến email đăng ký của bạn...</span>';
  modal.classList.add('open');
  
  sendOtpRequest(cccd);
}

async function sendOtpRequest(cccd) {
  const desc = document.getElementById('otp-modal-desc');
  try {
    const res = await fetch('./api.php?action=send_otp', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ cccd: cccd })
    });
    
    const data = await res.json();
    if (!res.ok) {
      throw new Error(data.error || 'Không thể gửi mã OTP.');
    }
    
    if (data.cccd) {
      currentCccd = data.cccd;
      const inputEl = document.getElementById('cccd-' + currentVong);
      if (inputEl) {
        inputEl.value = data.cccd;
      }
    }
    
    desc.innerHTML = `Mã OTP đã được gửi thành công đến email đăng ký của bạn.<br><span style="color: #059669; font-weight: 600;">(Hãy mở file <code style="background:var(--bp);padding:2px 4px;border-radius:4px">sent_emails.log</code> trong thư mục dự án để lấy mã OTP)</span>`;
    startCooldown(60);
  } catch (e) {
    console.error(e);
    desc.innerHTML = `<span style="color: #dc2626; font-weight: 600;">❌ Lỗi: ${e.message}</span>`;
  }
}

async function submitOtp() {
  const otpInput = document.getElementById('otp-input');
  const otp = otpInput.value.trim();
  const desc = document.getElementById('otp-modal-desc');
  const btnVerify = document.getElementById('btn-verify-otp');
  
  if (otp.length !== 6) {
    alert('Vui lòng nhập đầy đủ mã OTP 6 chữ số.');
    return;
  }
  
  btnVerify.disabled = true;
  btnVerify.textContent = 'Đang xác thực...';
  
  try {
    const res = await fetch('./api.php?action=verify_otp', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ cccd: currentCccd, otp: otp })
    });
    
    const data = await res.json();
    if (!res.ok) {
      throw new Error(data.error || 'Xác thực OTP thất bại.');
    }
    
    sessionStorage.setItem('otp_token_' + currentCccd, data.token);
    closeOtpModal();
    
    // Tải lại kết quả
    lookup(currentVong);
  } catch (e) {
    console.error(e);
    alert('Lỗi xác thực: ' + e.message);
  } finally {
    btnVerify.disabled = false;
    btnVerify.textContent = 'Xác nhận';
  }
}

function closeOtpModal() {
  const modal = document.getElementById('otp-modal');
  modal.classList.remove('open');
  clearInterval(cooldownInterval);
  resendCooldown = 0;
  
  // Cập nhật lại kết quả khu vực hiển thị nếu hủy
  const el = document.getElementById('result-' + currentVong);
  if (el) {
    el.innerHTML = '<div class="res-card res-fail"><div class="res-icon">⚠️</div><div class="res-txt"><h4>Cần xác thực OTP</h4><p>Vui lòng hoàn tất xác thực OTP để tra cứu chi tiết kết quả.</p></div></div>';
  }
}

async function resendOtp(event) {
  event.preventDefault();
  if (resendCooldown > 0) return;
  
  const desc = document.getElementById('otp-modal-desc');
  desc.innerHTML = '<span style="color: var(--bm);">⏳ Đang gửi lại mã OTP...</span>';
  await sendOtpRequest(currentCccd);
}

function startCooldown(seconds) {
  resendCooldown = seconds;
  const btnResend = document.getElementById('btn-resend-otp');
  const cooldownSpan = document.getElementById('otp-cooldown');
  
  btnResend.style.opacity = '0.5';
  btnResend.style.pointerEvents = 'none';
  cooldownSpan.style.display = 'inline';
  cooldownSpan.textContent = `(${resendCooldown}s)`;
  
  clearInterval(cooldownInterval);
  cooldownInterval = setInterval(() => {
    resendCooldown--;
    if (resendCooldown <= 0) {
      clearInterval(cooldownInterval);
      btnResend.style.opacity = '1';
      btnResend.style.pointerEvents = 'auto';
      cooldownSpan.style.display = 'none';
    } else {
      cooldownSpan.textContent = `(${resendCooldown}s)`;
    }
  }, 1000);
}

function renderResults(found, el, vong, donvi) {
  if (!Array.isArray(found) || found.length === 0) {
    el.innerHTML = `<div class="res-card res-pending"><div class="res-icon">📋</div><div class="res-txt"><h4>Không tìm thấy thông tin</h4><p>Thông tin tra cứu chưa khớp với danh sách dự tuyển. Vui lòng kiểm tra lại số CCCD hoặc họ tên của bạn.</p></div></div>`;
    return;
  }

  // Lọc theo đơn vị nếu người dùng nhập đơn vị lọc trên UI (so khớp không dấu mềm dẻo)
  const filtered = found.filter(r => {
    if (!donvi) return true;
    const cleanDonvi = cleanSchoolName(donvi);
    const cleanTruong = cleanSchoolName(r.truong);
    return cleanTruong.includes(cleanDonvi) || cleanDonvi.includes(cleanTruong);
  });

  if (filtered.length === 0) {
    el.innerHTML = `<div class="res-card res-pending"><div class="res-icon">📋</div><div class="res-txt"><h4>Không tìm thấy thông tin phù hợp</h4><p>Không tìm thấy hồ sơ tại đơn vị dự tuyển đã nhập.</p></div></div>`;
    return;
  }

  let html = '';
  filtered.forEach(r => {
    const trangThai = r.trang_thai || 'Chờ duyệt';
    const diem = r.diem;
    const ketQua = r.ket_qua;
    
    let cardClass = 'res-pending', icon = '⏳', title = 'Đang thẩm định hồ sơ';
    let desc = 'Hồ sơ đang được Hội đồng tuyển dụng kiểm tra điều kiện tiêu chuẩn.';

    if (vong === 'v1') {
      if (trangThai === 'Đã duyệt') {
        cardClass = 'res-pass'; icon = '🎉'; title = 'ĐỦ ĐIỀU KIỆN VÀO VÒNG 2';
        desc = 'Chúc mừng ứng viên đã vượt qua Vòng 1 và đủ điều kiện tham gia phỏng vấn/thực hành Vòng 2.';
      } else if (trangThai === 'Từ chối') {
        cardClass = 'res-fail'; icon = '❌'; title = 'HỒ SƠ KHÔNG ĐẠT YÊU CẦU';
        desc = 'Hồ sơ đăng ký dự tuyển không đáp ứng đủ các điều kiện hoặc thiếu giấy tờ theo quy định.';
      }
      
      html += `<div class="res-card ${cardClass}">
        <div class="res-icon">${icon}</div>
        <div class="res-txt">
          <h4>${title}</h4>
          <p><strong>${r.ho_ten}</strong> — CCCD: ${r.cccd}</p>
          <p>Vị trí: ${r.vi_tri} | Trường đăng ký: ${r.truong} (${r.cap})</p>
          <p style="margin-top:4px;font-size:.78rem;opacity:.85">${desc}</p>
        </div>
      </div>`;
    } else {
      // Kết quả Vòng 2
      if (trangThai !== 'Đã duyệt') {
        cardClass = 'res-fail'; icon = '⚠️'; title = 'CHƯA ĐỦ ĐIỀU KIỆN XÉT VÒNG 2';
        desc = 'Ứng viên chưa vượt qua Vòng 1 hoặc hồ sơ chưa được duyệt nên chưa có điểm thi Vòng 2.';
      } else if (diem === null || diem === undefined || diem === '') {
        cardClass = 'res-pending'; icon = '⏳'; title = 'ĐANG CẬP NHẬT ĐIỂM THI';
        desc = 'Ứng viên đã đủ điều kiện dự thi Vòng 2. Điểm thi đang được Hội đồng tuyển dụng tổng hợp và cập nhật.';
      } else {
        desc = `Điểm thi Vòng 2: ${diem} điểm.`;
        if (ketQua === 'Trúng tuyển') {
          cardClass = 'res-pass'; icon = '🎉'; title = 'TRÚNG TUYỂN CHÍNH THỨC';
          desc += ' Chúc mừng bạn đã trúng tuyển viên chức giáo viên năm 2026!';
        } else if (ketQua === 'Không trúng') {
          cardClass = 'res-fail'; icon = '❌'; title = 'KHÔNG TRÚNG TUYỂN';
          desc += ' Rất tiếc, điểm số của bạn chưa đủ điều kiện trúng tuyển đợt này.';
        } else {
          cardClass = 'res-pending'; icon = '📋'; title = 'ĐÃ CÓ ĐIỂM THI - ĐANG XÉT DUYỆT';
          desc += ' Kết quả trúng tuyển cuối cùng đang được xét duyệt và công bố.';
        }
      }

      const scoreHtml = (trangThai === 'Đã duyệt' && diem !== null && diem !== undefined && diem !== '') ? `<div class="res-score"><div class="sn">${diem}</div><div class="sl">Điểm Vòng 2</div></div>` : '';

      html += `<div class="res-card ${cardClass}">
        <div class="res-icon">${icon}</div>
        <div class="res-txt">
          <h4>${title}</h4>
          <p><strong>${r.ho_ten}</strong> — CCCD: ${r.cccd}</p>
          <p>Vị trí: ${r.vi_tri} | Trường đăng ký: ${r.truong} (${r.cap})</p>
          <p style="margin-top:4px;font-size:.78rem;opacity:.85">${desc}</p>
        </div>
        ${scoreHtml}
      </div>`;
    }
  });
  el.innerHTML = html;
}

function switchLTab(id, btn) {
  document.querySelectorAll('.ltab').forEach(b => b.classList.remove('active'));
  document.querySelectorAll('.ltab-pane').forEach(p => p.classList.remove('active'));
  document.getElementById('ltab-' + id).classList.add('active');
  btn.classList.add('active');
}
