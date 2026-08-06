const CACHE_NAME = "sugarcane-audit-v1";
const urlsToCache = [
  "./",
  "./index.php",
  "./index.css",
  "./icon/unnamed.png",
  "./icon/bg.jpg"
];

self.addEventListener("install", (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      // ใช้ catch เพื่อไม่ให้หยุดการทำงานถ้าโหลดบางไฟล์ไม่สำเร็จ
      return cache.addAll(urlsToCache).catch(err => console.log('Cache error', err));
    })
  );
});

self.addEventListener("fetch", (event) => {
  // รองรับแค่ GET request
  if (event.request.method !== 'GET') return;
  
  event.respondWith(
    caches.match(event.request).then((response) => {
      // ถ้ามีในแคช ให้คืนค่าจากแคช ถ้าไม่มีให้ดึงจากเน็ต
      return response || fetch(event.request).catch(() => {
          // ถ้า offline และดึงไม่สำเร็จ
          console.log('Offline and not in cache');
      });
    })
  );
});
