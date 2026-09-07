# Online Exam System (ប្រព័ន្ធគ្រប់គ្រង និងប្រឡងអនឡាញ)

> **Online Examination & Student Assessment Portal**  
> បង្កើតឡើងដោយប្រើប្រាស់ **Laravel 13**, **Vue.js 3**, **Tailwind CSS**, និង **KaTeX**

---

## 📖 ឯកសារណែនាំលម្អិត (Full Documentation)
សូមចូលទៅកាន់ឯកសារ [PROJECT_DOCUMENTATION.md](file:///d:/reancode.com%20c++/Web%20Laravel/Onlinexam/my-app/PROJECT_DOCUMENTATION.md) ដើម្បីមើលសេចក្តីលម្អិតពេញលេញអំពី៖
1. **ស្ថាបត្យកម្មប្រព័ន្ធ (System Architecture)**
2. **តួនាទី និងសិទ្ធិប្រើប្រាស់ (Super Admin, Admin, Student)**
3. **មុខងារប្រឡង, ការ Import ពី Word (.docx) & រូបមន្តគណិត KaTeX**
4. **ប្រព័ន្ធតាមដានផ្ទាល់ (Live Exam Monitor) & Anti-Cheat System**
5. **រចនាសម្ព័ន្ធទិន្នន័យ (Database Schema & ER Diagrams)**
6. **បញ្ជី API Endpoints ទាំងអស់**
7. **ការដំឡើង និងការដាក់ដំណើរការលើ Web Hosting (Deployment Guide)**

---

## 🚀 របៀបដំណើរការ Local Development

```bash
# ១. ដំឡើង Dependencies
composer install
npm install

# ២. កំណត់ Environment & Database
cp .env.example .env
php artisan key:generate
php artisan migrate --seed

# ៣. ដំណើរការ Server
php artisan serve
npm run dev
```

---

## 📦 របៀប Build សម្រាប់ Production
```bash
npm run build
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
