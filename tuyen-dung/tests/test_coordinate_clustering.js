// test_coordinate_clustering.js - Kiểm thử thuật toán gom cụm toạ độ Y và định biên động

const page2Items = [
    // --- Các tiêu đề mục ---
    { str: "III. THÔNG TIN VỀ QUÁ TRÌNH ĐÀO TẠO", transform: [0, 0, 0, 0, 183.60, 367.56] },
    { str: "IV. THÔNG TIN VỀ QUÁ TRÌNH CÔNG TÁC (nếu có)", transform: [0, 0, 0, 0, 157.56, 120.60] },

    // --- Các tiêu đề cột trong bảng (Cần được loại trừ) ---
    { str: "Tên trường, cơ", transform: [0, 0, 0, 0, 79.44, 312.60] },
    { str: "sở đào tạo cấp", transform: [0, 0, 0, 0, 81.24, 298.80] },
    { str: "Ngày, tháng,", transform: [0, 0, 0, 0, 163.44, 326.40] },
    { str: "năm cấp văn", transform: [0, 0, 0, 0, 163.20, 312.60] },
    { str: "bằng, chứng", transform: [0, 0, 0, 0, 164.28, 298.80] },
    { str: "chỉ", transform: [0, 0, 0, 0, 188.28, 285.00] },
    { str: "Trình độ", transform: [0, 0, 0, 0, 238.56, 319.44] },
    { str: "văn bằng,", transform: [0, 0, 0, 0, 236.40, 305.64] },
    { str: "chứng chỉ", transform: [0, 0, 0, 0, 236.40, 291.84] },

    // Hàng 1 (Data)
    { str: "Trường", transform: [0, 0, 0, 0, 79.44, 257.76] },
    { str: "Đại học", transform: [0, 0, 0, 0, 115.56, 257.76] },
    { str: "Đồng Tháp", transform: [0, 0, 0, 0, 90.60, 237.12] },
    { str: "22/05/2026", transform: [0, 0, 0, 0, 168.60, 247.44] },
    { str: "Đại", transform: [0, 0, 0, 0, 242.64, 247.44] },
    { str: "học", transform: [0, 0, 0, 0, 259.92, 247.44] },
    { str: "D0034798", transform: [0, 0, 0, 0, 280.32, 247.44] },
    { str: "Sư", transform: [0, 0, 0, 0, 358.80, 257.76] },
    { str: "phạm", transform: [0, 0, 0, 0, 372.00, 257.76] },
    { str: "Tin Học", transform: [0, 0, 0, 0, 360.36, 237.12] },
    { str: "Sư", transform: [0, 0, 0, 0, 409.44, 257.76] },
    { str: "phạm", transform: [0, 0, 0, 0, 425.64, 257.76] },
    { str: "Tin Học", transform: [0, 0, 0, 0, 411.12, 237.12] },
    { str: "Chính", transform: [0, 0, 0, 0, 458.40, 257.76] },
    { str: "quy", transform: [0, 0, 0, 0, 464.04, 237.12] },
    { str: "Giỏi", transform: [0, 0, 0, 0, 501.84, 247.44] },

    // Hàng 2 (Data)
    { str: "Trung", transform: [0, 0, 0, 0, 76.44, 209.88] },
    { str: "tâm Ngoại", transform: [0, 0, 0, 0, 108.72, 209.88] },
    { str: "Ngữ và Tin học,", transform: [0, 0, 0, 0, 78.60, 189.24] },
    { str: "Trường", transform: [0, 0, 0, 0, 79.44, 168.48] },
    { str: "Đại học", transform: [0, 0, 0, 0, 115.56, 168.48] },
    { str: "Đồng Tháp", transform: [0, 0, 0, 0, 90.60, 147.84] },
    { str: "03/05/2024", transform: [0, 0, 0, 0, 168.60, 178.80] },
    { str: "Chứng", transform: [0, 0, 0, 0, 236.40, 178.80] },
    { str: "chỉ", transform: [0, 0, 0, 0, 236.40, 178.80] },
    { str: "10217", transform: [0, 0, 0, 0, 286.56, 178.80] },
    { str: "Tiếng", transform: [0, 0, 0, 0, 416.88, 189.24] },
    { str: "Anh", transform: [0, 0, 0, 0, 420.60, 168.48] },
    { str: "B1", transform: [0, 0, 0, 0, 473.04, 178.80] }
];

let yLimitTop = 270;
let yLimitBottom = 125;

let headerIII_Y = -1;
let headerIV_Y = -1;

page2Items.forEach(item => {
    const str = item.str.trim();
    const y = item.transform[5];
    if (str.includes('III') && (str.toUpperCase().includes('ĐÀO TẠO') || str.toUpperCase().includes('DAO TAO'))) {
        headerIII_Y = y;
    }
    if (str.includes('IV') && (str.toUpperCase().includes('CÔNG TÁC') || str.toUpperCase().includes('CONG TAC'))) {
        headerIV_Y = y;
    }
});

console.log("Header III Y:", headerIII_Y);
console.log("Header IV Y:", headerIV_Y);

if (headerIII_Y === -1) headerIII_Y = 367;
if (headerIV_Y === -1) headerIV_Y = 120;

// Tìm toạ độ dòng tiêu đề bảng
let minHeaderY = -1;
page2Items.forEach(item => {
    const str = item.str.trim().toUpperCase();
    const y = item.transform[5];
    if (y > headerIV_Y && y < headerIII_Y) {
        if (str.includes('TÊN TRƯỜNG') || str.includes('NGÀY') || str.includes('TRÌNH ĐỘ') || str.includes('SỐ HIỆU') || str.includes('CHUYÊN NGÀNH') || str.includes('NGÀNH') || str.includes('HÌNH THỨC') || str.includes('XẾP LOẠI') || str.includes('VĂN BẰNG') || str.includes('CHỨNG CHỈ') || str.includes('CƠ SỞ')) {
            if (minHeaderY === -1 || y < minHeaderY) {
                minHeaderY = y;
            }
        }
    }
});

console.log("Min Header Y found:", minHeaderY);

if (minHeaderY !== -1) {
    yLimitTop = minHeaderY - 10;
} else {
    yLimitTop = headerIII_Y - 90;
}
yLimitBottom = headerIV_Y + 5;

console.log("Quét Y từ:", yLimitBottom, "đến", yLimitTop);

const dtItems = page2Items.filter(item => {
    const y = item.transform[5];
    return y >= yLimitBottom && y < yLimitTop;
});

// Thu thập điểm neo
const anchorYs = [];
dtItems.forEach(item => {
    const x = item.transform[4];
    const y = item.transform[5];
    const str = item.str.trim();
    if (!str) return;

    if ((x >= 150 && x < 350) || x >= 480) {
        anchorYs.push(y);
    }
});

console.log("Toạ độ điểm neo thu thập được:", anchorYs);

// Gom cụm
const rowCenters = [];
anchorYs.sort((a, b) => b - a);
anchorYs.forEach(y => {
    let found = false;
    for (let i = 0; i < rowCenters.length; i++) {
        if (Math.abs(rowCenters[i].sum / rowCenters[i].count - y) < 20) {
            rowCenters[i].sum += y;
            rowCenters[i].count++;
            found = true;
            break;
        }
    }
    if (!found) {
        rowCenters.push({ sum: y, count: 1 });
    }
});

let sortedRowCenters = rowCenters
    .map(c => c.sum / c.count)
    .sort((a, b) => b - a);

console.log("Các cụm toạ độ hàng tìm thấy:", sortedRowCenters);

if (sortedRowCenters.length > 0) {
    const rows = sortedRowCenters.map(() => ({
        coSo: [], ngayCap: [], trinhDo: [], soHieu: [], chuyenNganh: [], nganhDaoTao: [], hinhThuc: [], xepLoai: []
    }));

    dtItems.forEach(item => {
        const x = item.transform[4];
        const y = item.transform[5];
        const str = item.str.trim();
        if (!str) return;

        // Tìm hàng gần nhất
        let rowIndex = 0;
        let minDiff = Math.abs(sortedRowCenters[0] - y);
        for (let i = 1; i < sortedRowCenters.length; i++) {
            const diff = Math.abs(sortedRowCenters[i] - y);
            if (diff < minDiff) {
                minDiff = diff;
                rowIndex = i;
            }
        }

        const r = rows[rowIndex];
        if (x < 150) {
            r.coSo.push({ x, y, str });
        } else if (x >= 150 && x < 230) {
            r.ngayCap.push({ x, str });
        } else if (x >= 230 && x < 280) {
            r.trinhDo.push({ x, str });
        } else if (x >= 280 && x < 350) {
            r.soHieu.push({ x, str });
        } else if (x >= 350 && x < 405) {
            r.chuyenNganh.push({ x, y, str });
        } else if (x >= 405 && x < 450) {
            r.nganhDaoTao.push({ x, y, str });
        } else if (x >= 450 && x < 490) {
            r.hinhThuc.push({ x, str });
        } else {
            r.xepLoai.push({ x, str });
        }
    });

    console.log("\n--- KẾT QUẢ PHÂN TÍCH ---");
    rows.forEach((r, idx) => {
        r.coSo.sort((a, b) => {
            if (Math.abs(a.y - b.y) < 5) return a.x - b.x;
            return b.y - a.y;
        });
        let coSoVal = r.coSo.map(it => it.str).join(' ').trim();

        r.ngayCap.sort((a, b) => a.x - b.x);
        let ngayCapVal = r.ngayCap.map(it => it.str).join(' ').trim();

        r.trinhDo.sort((a, b) => a.x - b.x);
        let trinhDoVal = r.trinhDo.map(it => it.str).join(' ').trim();

        r.soHieu.sort((a, b) => a.x - b.x);
        let soHieuVal = r.soHieu.map(it => it.str).join(' ').trim();

        r.chuyenNganh.sort((a, b) => {
            if (Math.abs(a.y - b.y) < 5) return a.x - b.x;
            return b.y - a.y;
        });
        let chuyenNganhVal = r.chuyenNganh.map(it => it.str).join(' ').trim();

        r.nganhDaoTao.sort((a, b) => {
            if (Math.abs(a.y - b.y) < 5) return a.x - b.x;
            return b.y - a.y;
        });
        let nganhDaoTaoVal = r.nganhDaoTao.map(it => it.str).join(' ').trim();

        r.hinhThuc.sort((a, b) => a.x - b.x);
        let hinhThucVal = r.hinhThuc.map(it => it.str).join(' ').trim();

        r.xepLoai.sort((a, b) => a.x - b.x);
        let xepLoaiVal = r.xepLoai.map(it => it.str).join(' ').trim();

        console.log(`HÀNG ${idx + 1}:`);
        console.log(" - Cơ sở đào tạo:", coSoVal);
        console.log(" - Ngày cấp:", ngayCapVal);
        console.log(" - Trình độ:", trinhDoVal);
        console.log(" - Số hiệu:", soHieuVal);
        console.log(" - Chuyên ngành:", chuyenNganhVal);
        console.log(" - Ngành đào tạo:", nganhDaoTaoVal);
        console.log(" - Hình thức:", hinhThucVal);
        console.log(" - Xếp loại:", xepLoaiVal);
    });
}
