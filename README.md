# 🏆 Tournament Management System

Một hệ thống quản lý giải đấu hoàn chỉnh được xây dựng bằng Laravel 11 với Clean Architecture và các design patterns tiên tiến.

## 🚀 Tính năng chính

### 🎯 Tournament Management
- Tạo và quản lý các loại giải (đơn nam/nữ, đôi nam/nữ/mixed)
- Bracket generation tự động (single/double elimination)
- Scheduling matches với conflict detection
- Real-time tournament updates

### 👤 Player & Team Management
- Player registration với skill rating system (ELO-like)
- Team formation và management cho doubles
- Player statistics tracking chi tiết
- Team partnerships management

### 🏅 Match System
- Detailed scoring system với JSON flexibility
- Match scheduling và court assignment
- Officials tracking
- Broadcasting support
- Event timeline tracking

## 🏗️ Architecture & Design Patterns

### Clean Architecture
- **Domain Layer**: Pure business logic
- **Application Layer**: Use Cases, Commands, Queries, DTOs
- **Infrastructure Layer**: Database, External services
- **Presentation Layer**: Controllers, Resources, Requests

### Design Patterns Implemented
- ✅ **Repository Pattern** - Clean data access abstraction
- ✅ **Strategy Pattern** - Different tournament formats
- ✅ **Observer Pattern** - Real-time notifications
- ✅ **Factory Pattern** - Tournament creation
- ✅ **Command Pattern** - Match operations
- 🔄 **CQRS** - Command Query Responsibility Segregation (In Progress)
- 🔄 **Event Sourcing** - Match history (Planned)

## 🛠️ Tech Stack

### Backend
- **Laravel 11** với PHP 8.3
- **MySQL** (primary database)
- **Redis** (caching)
- **Elasticsearch** (search) - Planned
- **WebSocket** (Laravel Reverb) - Planned

### Development & DevOps
- **Docker** cho development environment
- **GitHub Actions** cho CI/CD - Planned
- **Laravel Telescope** cho debugging
- **PHPUnit** cho testing

## 📊 Database Schema

Hệ thống sử dụng 6 tables chính:
- `tournaments` - Tournament management
- `players` - Player profiles với skill ratings
- `teams` - Doubles partnerships
- `tournament_participants` - Registration management
- `matches` - Match data với detailed scoring
- `users` - Authentication

## 🚦 Installation & Setup

### Prerequisites
- PHP 8.3+
- Composer
- MySQL 8.0+
- Redis (optional)
- Node.js (for frontend assets)

### Installation Steps

1. **Clone repository**
   ```bash
   git clone https://github.com/2hm1901/tournament_management.git
   cd tournament_management
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Database setup**
   ```bash
   # Configure database in .env file
   php artisan migrate
   ```

5. **Start development server**
   ```bash
   php artisan serve
   ```

## 📈 Development Roadmap

### Phase 1: Foundation ✅
- [x] Database schema design
- [x] Domain models implementation
- [x] Repository pattern setup
- [x] Basic authentication

### Phase 2: Core Features 🔄
- [ ] Tournament CRUD operations
- [ ] Player registration system
- [ ] Match scheduling
- [ ] Basic API endpoints

### Phase 3: Advanced Features 📋
- [ ] Real-time updates với WebSocket
- [ ] Advanced search với Elasticsearch
- [ ] Performance optimization
- [ ] Comprehensive testing

### Phase 4: Production 📋
- [ ] CI/CD pipeline
- [ ] Docker containerization
- [ ] Monitoring & logging
- [ ] Security hardening

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit
```

## 📝 API Documentation

API documentation sẽ được cung cấp sau khi hoàn thành Phase 2.

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License.

## 👨‍💻 Author

- **GitHub**: [@2hm1901](https://github.com/2hm1901)

## 🙏 Acknowledgments

- Laravel Community
- Clean Architecture principles by Robert C. Martin
- Domain-Driven Design concepts

---

⭐ Star this repository if you find it helpful!
