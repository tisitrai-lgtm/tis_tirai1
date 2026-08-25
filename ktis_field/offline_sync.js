/**
 * offline_sync.js — ระบบบันทึกและซิงค์ข้อมูลออฟไลน์ (IndexedDB & Auto-Sync)
 * KTIS SMART FIELD - ระบบปฏิบัติงานไร่อ้อย
 */

const KTIS_OFFLINE = (function() {
    const DB_NAME = 'ktis_field_offline_db';
    const DB_VERSION = 1;
    const STORE_NAME = 'pending_inspections';

    let dbInstance = null;

    // 1. เปิด / สร้าง IndexedDB
    function openDB() {
        return new Promise((resolve, reject) => {
            if (dbInstance) return resolve(dbInstance);

            const request = indexedDB.open(DB_NAME, DB_VERSION);

            request.onupgradeneeded = function(e) {
                const db = e.target.result;
                if (!db.objectStoreNames.contains(STORE_NAME)) {
                    db.createObjectStore(STORE_NAME, { keyPath: 'offline_id', autoIncrement: true });
                }
            };

            request.onsuccess = function(e) {
                dbInstance = e.target.result;
                resolve(dbInstance);
            };

            request.onerror = function(e) {
                console.error('IndexedDB open error:', e);
                reject(e);
            };
        });
    }

    // 2. บันทึกผลตรวจลง IndexedDB (โหมดออฟไลน์)
    async function saveInspectionOffline(record) {
        const db = await openDB();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(STORE_NAME, 'readwrite');
            const store = tx.objectStore(STORE_NAME);
            record.created_offline_at = new Date().toISOString();

            const req = store.add(record);
            req.onsuccess = () => {
                updateOfflineUI();
                resolve(req.result);
            };
            req.onerror = (e) => reject(e);
        });
    }

    // 3. ดึงรายการทั้งหมดที่รอซิงค์
    async function getAllPending() {
        const db = await openDB();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(STORE_NAME, 'readonly');
            const store = tx.objectStore(STORE_NAME);
            const req = store.getAll();
            req.onsuccess = () => resolve(req.result || []);
            req.onerror = (e) => reject(e);
        });
    }

    // 4. ลบรายการที่ซิงค์สำเร็จแล้ว
    async function deletePending(offline_id) {
        const db = await openDB();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(STORE_NAME, 'readwrite');
            const store = tx.objectStore(STORE_NAME);
            const req = store.delete(offline_id);
            req.onsuccess = () => {
                updateOfflineUI();
                resolve();
            };
            req.onerror = (e) => reject(e);
        });
    }

    // 5. นับจำนวนรายการที่รอซิงค์
    async function countPending() {
        const db = await openDB();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(STORE_NAME, 'readonly');
            const store = tx.objectStore(STORE_NAME);
            const req = store.count();
            req.onsuccess = () => resolve(req.result || 0);
            req.onerror = () => resolve(0);
        });
    }

    // 6. ซิงค์ข้อมูลทั้งหมดขึ้นเซิร์ฟเวอร์
    let isSyncing = false;
    async function syncAllPending() {
        if (isSyncing || !navigator.onLine) return;
        
        const items = await getAllPending();
        if (items.length === 0) return;

        isSyncing = true;
        showSyncToast('กำลังซิงค์ข้อมูลตรวจเช็ค ' + items.length + ' รายการขึ้นเซิร์ฟเวอร์...');

        let successCount = 0;

        for (const item of items) {
            try {
                const response = await fetch('api_offline_sync.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(item)
                });
                const resData = await response.json();

                if (resData.status === 'success') {
                    await deletePending(item.offline_id);
                    successCount++;
                }
            } catch (err) {
                console.warn('Sync single item failed, will retry later:', err);
            }
        }

        isSyncing = false;
        updateOfflineUI();

        if (successCount > 0) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: `ซิงค์ข้อมูลสำเร็จ ${successCount} รายการ ✅`,
                    showConfirmButton: false,
                    timer: 3000
                });
            }
        }
    }

    // 7. แถบแจ้งเตือนสถานะ Offline / Sync
    function updateOfflineUI() {
        let banner = document.getElementById('offline-sync-banner');
        if (!banner) {
            banner = document.createElement('div');
            banner.id = 'offline-sync-banner';
            banner.style.cssText = 'position:fixed; bottom:16px; left:50%; transform:translateX(-50%); z-index:99999; display:none; align-items:center; gap:10px; padding:10px 18px; border-radius:30px; font-family:"Sarabun",sans-serif; font-size:0.84rem; font-weight:700; box-shadow:0 8px 24px rgba(0,0,0,0.25); transition:all 0.3s cubic-bezier(0.16,1,0.3,1);';
            document.body.appendChild(banner);
        }

        countPending().then(count => {
            if (!navigator.onLine) {
                banner.style.display = 'flex';
                banner.style.background = '#e11d48';
                banner.style.color = '#ffffff';
                banner.innerHTML = `<i class="fa-solid fa-plane-slash"></i> <span>โหมดออฟไลน์ (ไม่มีสัญญาณเน็ต)</span> ${count > 0 ? `<span style="background:rgba(0,0,0,0.25); padding:2px 8px; border-radius:12px; font-size:0.75rem;">รอซิงค์ ${count} รายการ</span>` : ''}`;
            } else if (count > 0) {
                banner.style.display = 'flex';
                banner.style.background = '#0284c7';
                banner.style.color = '#ffffff';
                banner.innerHTML = `<i class="fa-solid fa-cloud-arrow-up"></i> <span>มีข้อมูลรอซิงค์ ${count} รายการ</span> <button onclick="KTIS_OFFLINE.syncAllPending()" style="background:white; color:#0284c7; border:none; border-radius:14px; padding:3px 10px; font-weight:800; font-size:0.75rem; cursor:pointer; margin-left:4px;">ซิงค์ทันที</button>`;
            } else {
                banner.style.display = 'none';
            }
        });
    }

    function showSyncToast(text) {
        let banner = document.getElementById('offline-sync-banner');
        if (banner) {
            banner.style.display = 'flex';
            banner.style.background = '#0284c7';
            banner.style.color = '#ffffff';
            banner.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> <span>${text}</span>`;
        }
    }

    // Event Listeners for Network Changes
    window.addEventListener('online', () => {
        updateOfflineUI();
        syncAllPending();
    });

    window.addEventListener('offline', () => {
        updateOfflineUI();
    });

    document.addEventListener('DOMContentLoaded', () => {
        updateOfflineUI();
        if (navigator.onLine) {
            syncAllPending();
        }
    });

    return {
        saveInspectionOffline,
        getAllPending,
        countPending,
        syncAllPending,
        updateOfflineUI
    };
})();
