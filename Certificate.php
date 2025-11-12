<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>شهادتي</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet" />
  <link rel="icon" type="image/png" href="ChatGPT Image 4 juin 2025, 14_53_49.png">

  <style>
    body {
      margin: 0;
      font-family: 'Cairo', sans-serif;
      background: #001f2e;
      color: #e0f7fa;
      overflow-x: hidden;
    }

    #particles-js {
      position: fixed;
      width: 100%;
      height: 100%;
      z-index: -1;
    }

    .container {
      max-width: 1200px;
      margin: auto;
      padding: 50px 20px;
      text-align: center;
    }

    h1 {
      color: #00ffe7;
      font-size: 36px;
      margin-bottom: 20px;
      animation: fadeIn 2s ease-in-out;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .certificates {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
      justify-content: center;
    }

    .card {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid #00ffe7;
      border-radius: 12px;
      padding: 20px;
      width: 300px;
      cursor: pointer;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      position: relative;
    }

    .card:hover {
      transform: translateY(-10px);
      box-shadow: 0 0 20px #00ffe7;
    }

    .card h3 {
      color: #00ffe7;
      margin-bottom: 10px;
    }

    .card p {
      font-size: 14px;
      margin-bottom: 15px;
      color: #d0faff;
    }

    .card-buttons {
      display: flex;
      justify-content: space-between;
      margin-top: 10px;
    }

    .download-btn,
    .share-btn {
      flex: 1;
      margin: 0 5px;
      padding: 8px;
      border: none;
      border-radius: 6px;
      font-weight: bold;
      cursor: pointer;
      transition: background 0.3s ease;
    }

    .download-btn {
      background: #00ffe7;
      color: #001f2e;
    }

    .download-btn:hover {
      background: #00cbbf;
    }

    .share-btn {
      background: transparent;
      border: 1px solid #00ffe7;
      color: #00ffe7;
    }

    .share-btn:hover {
      background: #00ffe710;
    }

    /* Modal styles */
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      padding-top: 60px;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto;
      background-color: rgba(0,0,0,0.8);
    }

    .modal-content {
      margin: auto;
      display: block;
      max-width: 80%;
      max-height: 80vh;
      border-radius: 12px;
      box-shadow: 0 0 20px #00ffe7;
      animation: zoomIn 0.3s ease;
    }

    @keyframes zoomIn {
      from { transform: scale(0.7); opacity: 0; }
      to { transform: scale(1); opacity: 1; }
    }

    .close {
      position: absolute;
      top: 20px;
      right: 30px;
      color: #00ffe7;
      font-size: 36px;
      font-weight: bold;
      cursor: pointer;
      transition: color 0.3s;
    }

    .close:hover {
      color: #00cbbf;
    }

    .back-btn {
      margin-top: 40px;
      background: #00ffe7;
      color: #001f2e;
      padding: 12px 30px;
      border-radius: 8px;
      font-size: 16px;
      font-weight: bold;
      cursor: pointer;
      border: none;
      transition: background 0.3s;
    }

    .back-btn:hover {
      background: #00cbbf;
    }

    .quote {
      margin-bottom: 40px;
      font-size: 20px;
      font-weight: bold;
      color: #ffffff;
      animation: fadeIn 2s ease-in-out;
    }
          /* تخصيص شريط التمرير */
  ::-webkit-scrollbar {
    width: 10px;
  }

  ::-webkit-scrollbar-track {
    background: #0d1117; /* خلفية داكنة */
  }

  ::-webkit-scrollbar-thumb {
    background: #00f2ff; /* لون ساطع */
    border-radius: 10px;
    box-shadow: 0 0 10px #00f2ff; /* تأثير مضيء */
    transition: background 0.3s ease;
  }

  ::-webkit-scrollbar-thumb:hover {
    background: #00f2ffcc; /* عند التمرير يصبح أكثر سطوعًا */
  }
  </style>
</head>
<body>
  <div id="particles-js"></div>

  <div class="container">
    <h1>✨ شهادتي</h1>
    <div class="quote">"رحلتك نحو النجاح تبدأ بشهادة... لكن لا تنتهي بها ."</div>

    <div class="certificates">
      <div class="card" onclick="openModal('images/python_certificate.png')">
        <h3>شهادة Python</h3>
        <p>دورة أساسية في برمجة Python مع تطبيقات عملية.</p>
        <div class="card-buttons">
          <button class="download-btn" onclick="event.stopPropagation(); downloadCertificate('images/python_certificate.png')">تحميل</button>
          <button class="share-btn" onclick="event.stopPropagation(); shareCertificate('شهادة Python', 'images/python_certificate.png')">مشاركة</button>
        </div>
      </div>

      <div class="card" onclick="openModal('asistant virtuel ALX.jpeg')">
        <h3>شهادة JavaScript</h3>
        <p>شهادة إتمام دورة تطوير الواجهات باستخدام JS.</p>
        <div class="card-buttons">
          <button class="download-btn" onclick="event.stopPropagation(); downloadCertificate('asistant virtuel ALX.jpeg')">تحميل</button>
          <button class="share-btn" onclick="event.stopPropagation(); shareCertificate('شهادة JavaScript', 'asistant virtuel ALX.jpeg')">مشاركة</button>
        </div>
      </div>
    </div>

    <button class="back-btn" onclick="window.history.back()">رجوع</button>
  </div>

  <!-- Modal -->
  <div id="myModal" class="modal" onclick="closeModal(event)">
    <span class="close" title="إغلاق">&times;</span>
    <img class="modal-content" id="modalImage" alt="شهادة" />
  </div>

  <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
  <script>
    particlesJS("particles-js", {
      particles: {
        number: { value: 100, density: { enable: true, value_area: 800 } },
        color: { value: "#00ffe7" },
        shape: { type: "circle" },
        opacity: { value: 0.5 },
        size: { value: 3 },
        line_linked: {
          enable: true,
          distance: 150,
          color: "#00ffe7",
          opacity: 0.4,
          width: 1
        },
        move: { enable: true, speed: 2 }
      }
    });

    function openModal(imageSrc) {
      const modal = document.getElementById('myModal');
      const modalImg = document.getElementById('modalImage');
      modal.style.display = "block";
      modalImg.src = imageSrc;
    }

    function closeModal(event) {
      if(event.target.classList.contains('modal') || event.target.classList.contains('close')){
        document.getElementById('myModal').style.display = "none";
      }
    }

    function downloadCertificate(url) {
      const link = document.createElement('a');
      link.href = url;
      link.download = url.split('/').pop();
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    }

    function shareCertificate(title, imageUrl) {
      if (navigator.share) {
        navigator.share({
          title: title,
          text: `${title} - تم الحصول عليها من ISTALERNING`,
          url: imageUrl
        }).catch(console.error);
      } else {
        alert("المشاركة غير مدعومة في هذا المتصفح.");
      }
    }
  </script>
</body>
</html>
