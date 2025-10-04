# EBook Library

A fullstack online ebook library built with HTML/CSS/JavaScript frontend, PHP backend, and MySQL database.

## Features

### User Features
- **User Authentication**: Registration, login, and profile management
- **Book Browsing**: Browse books by category, author, or search
- **Advanced Search**: Search books by title, author, or description
- **Book Reading**: Built-in reader with customizable settings
- **Reading Progress**: Track reading progress across devices
- **Bookmarks**: Save and manage bookmarks with notes
- **Reviews & Ratings**: Rate and review books
- **Responsive Design**: Works on desktop, tablet, and mobile devices

### Admin Features
- **Dashboard**: Overview of library statistics
- **Book Management**: Add, edit, and delete books
- **Category Management**: Organize books into categories
- **User Management**: Manage user accounts
- **Review Management**: Moderate user reviews
- **File Upload**: Upload book files (PDF, EPUB, etc.)

## Technology Stack

- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Web Server**: Apache/Nginx
- **Dependencies**: Font Awesome icons

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache or Nginx web server
- Composer (optional, for dependency management)

### Setup Instructions

1. **Clone or download the project**
   ```bash
   git clone <repository-url>
   cd ebook-library
   ```

2. **Database Setup**
   - Create a MySQL database named `ebook_library`
   - Import the database schema:
     ```bash
     mysql -u root -p ebook_library < database/schema.sql
     ```

3. **Configuration**
   - Update database credentials in `config/database.php`:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_NAME', 'ebook_library');
     define('DB_USER', 'your_username');
     define('DB_PASS', 'your_password');
     ```

4. **File Permissions**
   - Create upload directories and set permissions:
     ```bash
     mkdir -p uploads/books
     chmod 755 uploads/books
     ```

5. **Web Server Configuration**
   - Point your web server document root to the project directory
   - Ensure mod_rewrite is enabled (for Apache)
   - Configure virtual host (optional)

6. **Access the Application**
   - Open your browser and navigate to your domain
   - Default admin credentials:
     - Username: `admin`
     - Password: `admin123`

## Project Structure

```
ebook-library/
├── admin/                 # Admin panel
│   ├── assets/
│   │   ├── css/
│   │   └── js/
│   ├── index.php
│   ├── books.php
│   └── ...
├── api/                   # API endpoints
│   ├── auth.php
│   ├── books.php
│   ├── categories.php
│   ├── reading-progress.php
│   ├── bookmarks.php
│   └── reviews.php
├── assets/                # Frontend assets
│   ├── css/
│   │   ├── style.css
│   │   ├── auth.css
│   │   ├── browse.css
│   │   └── reader.css
│   └── js/
│       ├── main.js
│       ├── browse.js
│       └── reader.js
├── config/                # Configuration files
│   └── database.php
├── database/              # Database files
│   └── schema.sql
├── includes/              # Shared PHP functions
│   └── functions.php
├── uploads/               # File uploads
│   └── books/
├── index.php              # Homepage
├── login.php              # Login page
├── register.php           # Registration page
├── browse.php             # Book browsing
├── search.php             # Search results
├── reader.php             # Book reader
└── README.md
```

## API Endpoints

### Authentication
- `POST /api/auth.php?action=login` - User login
- `POST /api/auth.php?action=register` - User registration
- `POST /api/auth.php?action=logout` - User logout
- `GET /api/auth.php?action=profile` - Get user profile
- `PUT /api/auth.php` - Update user profile

### Books
- `GET /api/books.php` - Get books list
- `POST /api/books.php` - Create new book (admin)
- `PUT /api/books.php?id={id}` - Update book (admin)
- `DELETE /api/books.php?id={id}` - Delete book (admin)

### Categories
- `GET /api/categories.php` - Get categories list
- `POST /api/categories.php` - Create category (admin)
- `PUT /api/categories.php?id={id}` - Update category (admin)
- `DELETE /api/categories.php?id={id}` - Delete category (admin)

### Reading Progress
- `GET /api/reading-progress.php` - Get user's reading progress
- `POST /api/reading-progress.php` - Update reading progress
- `DELETE /api/reading-progress.php?book_id={id}` - Delete reading progress

### Bookmarks
- `GET /api/bookmarks.php` - Get user's bookmarks
- `POST /api/bookmarks.php` - Create bookmark
- `PUT /api/bookmarks.php?id={id}` - Update bookmark
- `DELETE /api/bookmarks.php?id={id}` - Delete bookmark

### Reviews
- `GET /api/reviews.php` - Get reviews
- `POST /api/reviews.php` - Create review
- `PUT /api/reviews.php?id={id}` - Update review
- `DELETE /api/reviews.php?id={id}` - Delete review

## Usage

### For Users

1. **Registration**: Create an account to access the library
2. **Browse Books**: Use the browse page to explore books by category
3. **Search**: Use the search functionality to find specific books
4. **Read Books**: Click on a book to start reading
5. **Track Progress**: Your reading progress is automatically saved
6. **Add Bookmarks**: Bookmark important pages with notes
7. **Review Books**: Rate and review books you've read

### For Administrators

1. **Login**: Use admin credentials to access the admin panel
2. **Manage Books**: Add new books, edit existing ones, or remove books
3. **Organize Categories**: Create and manage book categories
4. **User Management**: View and manage user accounts
5. **Moderate Reviews**: Review and manage user reviews

## Customization

### Themes
The reader supports multiple themes:
- Light (default)
- Dark
- Sepia

### Reading Settings
Users can customize:
- Font size (12px - 24px)
- Line height (1.2 - 2.0)
- Theme selection

### File Types
Supported book file types:
- PDF
- EPUB
- MOBI
- TXT

## Security Features

- **Password Hashing**: Uses PHP's `password_hash()` function
- **SQL Injection Prevention**: Prepared statements throughout
- **XSS Protection**: Input sanitization and output escaping
- **CSRF Protection**: Token-based protection for forms
- **Session Management**: Secure session handling
- **File Upload Security**: Type and size validation

## Browser Support

- Chrome 60+
- Firefox 55+
- Safari 12+
- Edge 79+

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is open source and available under the [MIT License](LICENSE).

## Support

For support, please open an issue in the repository or contact the development team.

## Changelog

### Version 1.0.0
- Initial release
- User authentication system
- Book browsing and search
- Reading interface with progress tracking
- Admin panel for content management
- Responsive design
- API endpoints for all functionality
