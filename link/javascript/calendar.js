const monthNames = [
  "มกราคม",
  "กุมภาพันธ์",
  "มีนาคม",
  "เมษายน",
  "พฤษภาคม",
  "มิถุนายน",
  "กรกฎาคม",
  "สิงหาคม",
  "กันยายน",
  "ตุลาคม",
  "พฤศจิกายน",
  "ธันวาคม",
];

let bookings = []; // เก็บข้อมูลการจองทั้งหมด
let currentDate = new Date(); // เก็บเดือน-ปีปัจจุบัน

// ฟังก์ชันดึงข้อมูลการจองจากไฟล์ get_booking.php
async function fetchBookings() {
  try {
    const response = await fetch("../modal/get/get_booking.php");
    bookings = await response.json(); // แปลงข้อมูลเป็น JSON
    renderCalendar(currentDate); // สร้างปฏิทิน
  } catch (error) {
    console.error("Error fetching bookings:", error);
  }
}

// ฟังก์ชันสร้างปฏิทิน
function renderCalendar(date) {
  const month = date.getMonth();
  const year = date.getFullYear();

  document.getElementById(
    "month-year"
  ).textContent = `${monthNames[month]} ${year}`;

  const firstDay = new Date(year, month, 1);
  const lastDay = new Date(year, month + 1, 0);
  const totalDays = lastDay.getDate();
  const startDay = firstDay.getDay();

  const calendarBody = document.getElementById("calendar-body");
  calendarBody.innerHTML = "";

  let row = document.createElement("tr");
  const today = new Date();

  // สร้างช่องว่างก่อนวันแรกของเดือน (ถ้าวันที่ 1 ไม่ตรงกับวันอาทิตย์)
  for (let i = 0; i < startDay; i++) {
    const cell = document.createElement("td");
    row.appendChild(cell);
  }

  // สร้างเซลล์แต่ละวันในเดือน
  for (let day = 1; day <= totalDays; day++) {
    if (row.children.length === 7) {
      calendarBody.appendChild(row);
      row = document.createElement("tr");
    }

    const cell = document.createElement("td");
    const fullDate = `${year}-${String(month + 1).padStart(2, "0")}-${String(
      day
    ).padStart(2, "0")}`;

    // แสดงเลขวันที่
    cell.innerHTML = `<span class="day-number">${day}</span>`;

    // กรองข้อมูลการจองของวันที่นี้
    const bookingForDate = bookings.filter(
      (booking) => booking.date === fullDate
    );

    // เพิ่ม Event ให้คลิกได้ทุกวัน (มีหรือไม่มีการจอง)
    cell.addEventListener("click", () =>
      showBookingDetails(bookingForDate, fullDate)
    );

    // ถ้ามีการจอง ให้ใส่จุดสี
    if (bookingForDate.length > 0) {
      cell.classList.add("has-booking");

      // สร้าง container สำหรับจุดสี
      const dotsContainer = document.createElement("div");
      dotsContainer.classList.add("booking-dots");

      // แสดงจุดสีสำหรับแต่ละการจอง โดยใช้สีที่ตั้งไว้ในฐานข้อมูล
      bookingForDate.forEach((booking) => {
        const dot = document.createElement("div");
        dot.classList.add("booking-dot"); // กำหนดรูปทรงและขนาด dot ผ่าน CSS
        dot.style.backgroundColor = booking.color; // ใช้สีจากฐานข้อมูล (key "color")
        dotsContainer.appendChild(dot);
      });
      cell.appendChild(dotsContainer);
    }

    // ไฮไลต์วันที่ปัจจุบัน
    if (
      day === today.getDate() &&
      month === today.getMonth() &&
      year === today.getFullYear()
    ) {
      cell.classList.add("current-day");
    }

    row.appendChild(cell);
  }

  if (row.children.length > 0) {
    calendarBody.appendChild(row);
  }
}

// ฟังก์ชันแสดงรายละเอียดการจองใน Modal
function showBookingDetails(bookingsForDate, date) {
  const modalBody = document.getElementById("bookingModalBody");
  const modalTitle = document.getElementById("bookingModalLabel");

  if (bookingsForDate.length > 0) {
    modalTitle.textContent = `รายละเอียดการจอง (${date})`;

    let tablesHtml = bookingsForDate
      .map((booking, index) => {
        // สร้าง dotHtml จากสีในฐานข้อมูล (booking.color)
        const dotHtml = `
        <span 
            style="
                display: inline-block; 
                width: 12px; 
                height: 12px; 
                border-radius: 50%; 
                background-color: ${booking.color || "#ccc"}; 
                margin-right: 8px;
            ">
        </span>
    `;

        return `
        <table class="table table-striped table-bordered" style="margin-bottom: 20px; table-layout: auto; width: 100%;">
            <thead>
                <tr>
                    <th colspan="2" style="text-align: center;">
                        <!-- แทรก dot ไว้ด้านหน้าข้อความ -->
                        ${dotHtml}การจองครั้งที่ ${index + 1}
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <th style="text-align: left; vertical-align: top;">ผู้จอง</th>
                    <td style="text-align: left; vertical-align: top;">${
                      booking.booker_name || "ไม่ระบุ"
                    }</td>
                </tr>
                <tr>
                    <th style="text-align: left; vertical-align: top;">เบอร์ผู้จอง</th>
                    <td style="text-align: left; vertical-align: top;">${
                      booking.booker_phone || "-"
                    }</td>
                </tr>
                <tr>
                    <th style="text-align: left; vertical-align: top;">ชื่อห้อง</th>
                    <td style="text-align: left; vertical-align: top;">${
                      booking.room_name || "ไม่ระบุ"
                    }</td>
                </tr>
                <tr>
                    <th style="text-align: left; vertical-align: top;">ช่วงเวลาที่จอง</th>
                    <td style="text-align: left; vertical-align: top;">${
                      booking.booking_time || "-"
                    }</td>
                </tr>
                <tr>
                    <th style="text-align: left; vertical-align: top;">รายละเอียด</th>
                    <td style="text-align: left; vertical-align: top;">${
                      booking.details || "-"
                    }</td>
                </tr>
            </tbody>
        </table>
    `;
      })
      .join("");

    modalBody.innerHTML = tablesHtml;
  } else {
    modalTitle.textContent = `ไม่มีรายการจอง (${date})`;
    modalBody.innerHTML = `<p style="text-align: center; color: red;">ไม่มีการจองในวันนี้ :)</p>`;
  }

  // เปิด Modal
  const bookingModal = new bootstrap.Modal(
    document.getElementById("bookingModal")
  );
  bookingModal.show();
}

// ปุ่มเปลี่ยนเดือน (ก่อนหน้า)
document.getElementById("prev-month").addEventListener("click", () => {
  currentDate.setMonth(currentDate.getMonth() - 1);
  renderCalendar(currentDate);
});

// ปุ่มเปลี่ยนเดือน (ถัดไป)
document.getElementById("next-month").addEventListener("click", () => {
  currentDate.setMonth(currentDate.getMonth() + 1);
  renderCalendar(currentDate);
});

// ดึงข้อมูลจากฐานข้อมูลครั้งแรก เมื่อโหลดหน้าเสร็จ
fetchBookings();
