# 🤝 Contributing to JUZDY

Thank you for your interest in contributing to JUZDY! This guide will help you get started and ensure a smooth contribution process.

---

## 🌟 Ways to Contribute

There are many ways you can contribute to JUZDY:

- 🐛 **Report bugs** - Help us identify and fix issues
- 💡 **Suggest features** - Share your ideas for improvements
- 📖 **Improve documentation** - Help others understand JUZDY better
- 💻 **Submit code** - Fix bugs or implement new features
- 🧪 **Write tests** - Improve code coverage and reliability
- 🎨 **Create examples** - Show others how to use JUZDY
- 💬 **Answer questions** - Help other users in discussions

---

## 🚀 Getting Started

### 1. Fork the Repository

Visit [https://github.com/juzdy/juzdy](https://github.com/juzdy/juzdy) and click the "Fork" button.

### 2. Clone Your Fork

```bash
git clone https://github.com/YOUR-USERNAME/juzdy.git
cd juzdy
```

### 3. Set Up Development Environment

```bash
# Install dependencies
composer install

# Start development server (Docker)
./bin/docker-start.sh

# Or use PHP built-in server
cd pub && php -S localhost:8080
```

### 4. Create a Branch

```bash
git checkout -b feature/my-awesome-feature
# or
git checkout -b fix/bug-description
```

---

## 📝 Development Workflow

### Making Changes

1. **Write your code**
   - Follow PSR-12 coding standards
   - Add PHPDoc comments where appropriate
   - Keep changes focused and minimal

2. **Test your changes**
   - Ensure existing functionality still works
   - Add new tests for new features
   - Test manually in different scenarios

3. **Commit your changes**
   ```bash
   git add .
   git commit -m "Add feature: description of what you did"
   ```

4. **Push to your fork**
   ```bash
   git push origin feature/my-awesome-feature
   ```

5. **Create a Pull Request**
   - Go to the original JUZDY repository
   - Click "New Pull Request"
   - Select your fork and branch
   - Provide a clear description

---

## 📋 Code Style Guidelines

### PSR-12 Compliance

JUZDY follows [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standards.

**Key points:**

- Use 4 spaces for indentation (no tabs)
- Opening braces for classes and methods go on the next line
- Use camelCase for method names
- Use PascalCase for class names
- Add type hints to all parameters and return types

**Example:**

```php
<?php
namespace App\Http\Handler;

use Juzdy\Http\Handler;
use Juzdy\Http\RequestInterface;
use Juzdy\Http\ResponseInterface;

class MyHandler extends Handler
{
    public function __construct(
        private ServiceInterface $service
    ) {}

    public function handle(RequestInterface $request): ResponseInterface
    {
        $data = $this->service->getData();
        
        return $this->response()
            ->header('Content-Type', 'application/json')
            ->body(json_encode($data));
    }
}
```

### PHP Version

JUZDY requires **PHP 8.0 or higher**. Feel free to use modern PHP features:

- ✅ Constructor property promotion
- ✅ Named arguments
- ✅ Match expressions
- ✅ Union types
- ✅ Nullsafe operator
- ✅ Attributes

---

## 🧪 Testing Guidelines

### Running Tests

```bash
# Run all tests
composer test

# Run specific test
composer test -- --filter MyTest
```

### Writing Tests

Create tests in the `tests/` directory:

```php
<?php
namespace Tests\Http\Handler;

use PHPUnit\Framework\TestCase;
use App\Http\Handler\MyHandler;
use Juzdy\Http\Request;

class MyHandlerTest extends TestCase
{
    public function testHandlerReturnsSuccess(): void
    {
        $handler = new MyHandler();
        $request = new Request();
        
        $response = $handler->handle($request);
        
        $this->assertEquals(200, $response->status());
    }
}
```

---

## 📖 Documentation Guidelines

### Writing Good Documentation

- **Be clear and concise** - Explain concepts simply
- **Use examples** - Show, don't just tell
- **Be consistent** - Follow existing documentation style
- **Test code examples** - Ensure all code samples work
- **Use proper formatting** - Markdown syntax matters

### Documentation Structure

```markdown
# Title

Brief introduction paragraph.

## Section

Content with examples:

```php
// Code example
```

**Explanation** of the code.

### Subsection

More details...
```

---

## 🐛 Reporting Bugs

### Before Reporting

1. **Search existing issues** - Someone might have already reported it
2. **Verify it's a bug** - Make sure it's not a configuration issue
3. **Test with latest version** - The bug might already be fixed

### Creating a Bug Report

Include the following information:

**Title:** Brief, descriptive title

**Description:**
- What you expected to happen
- What actually happened
- Steps to reproduce

**Environment:**
- PHP version
- JUZDY version
- Operating system
- Web server (Apache/Nginx)

**Code samples:**
```php
// Minimal code that reproduces the issue
```

**Error messages:**
```
Stack traces or error logs
```

---

## 💡 Suggesting Features

### Before Suggesting

1. **Check if it already exists** - Search the documentation and issues
2. **Consider scope** - Is this feature relevant to most users?
3. **Think about implementation** - How would this work?

### Creating a Feature Request

**Title:** Clear, descriptive title

**Problem:** What problem does this solve?

**Proposed solution:** How would you implement it?

**Alternatives:** What other solutions did you consider?

**Example usage:**
```php
// How developers would use this feature
```

---

## 🔄 Pull Request Process

### Before Submitting

- ✅ Code follows PSR-12 standards
- ✅ All tests pass
- ✅ New tests added for new features
- ✅ Documentation updated if needed
- ✅ Commit messages are clear
- ✅ Branch is up to date with main

### PR Description Template

```markdown
## Description
Brief description of what this PR does.

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Documentation update
- [ ] Performance improvement
- [ ] Code refactoring

## Changes Made
- Change 1
- Change 2
- Change 3

## Testing
How did you test these changes?

## Screenshots (if applicable)
Add screenshots for UI changes

## Checklist
- [ ] My code follows the project's coding standards
- [ ] I have added tests for my changes
- [ ] All tests pass locally
- [ ] I have updated the documentation
```

### Review Process

1. **Automated checks** - CI/CD runs tests automatically
2. **Code review** - Maintainers review your code
3. **Feedback** - Address any requested changes
4. **Approval** - Once approved, your PR will be merged
5. **Credit** - You'll be listed as a contributor!

---

## 💬 Communication

### GitHub Discussions

Use [GitHub Discussions](https://github.com/juzdy/juzdy/discussions) for:
- General questions
- Feature discussions
- Show and tell
- Community support

### GitHub Issues

Use [GitHub Issues](https://github.com/juzdy/juzdy/issues) for:
- Bug reports
- Feature requests
- Documentation improvements

### Be Respectful

- Be kind and considerate
- Respect different opinions
- Focus on constructive feedback
- Help others learn and grow

---

## 📜 Code of Conduct

### Our Pledge

We are committed to providing a welcoming and inclusive experience for everyone, regardless of:
- Age, body size, disability, ethnicity, sex characteristics
- Gender identity and expression, level of experience
- Education, socio-economic status, nationality
- Personal appearance, race, religion, or sexual identity and orientation

### Our Standards

**Positive behavior:**
- Using welcoming and inclusive language
- Being respectful of differing viewpoints
- Gracefully accepting constructive criticism
- Focusing on what's best for the community
- Showing empathy towards others

**Unacceptable behavior:**
- Trolling, insulting, or derogatory comments
- Public or private harassment
- Publishing others' private information
- Other conduct inappropriate in a professional setting

### Enforcement

Report any unacceptable behavior to the project maintainers. All complaints will be reviewed and investigated promptly and fairly.

---

## 🏆 Recognition

### Contributors

All contributors will be:
- Listed in the project README
- Credited in release notes
- Celebrated in our community

### Maintainers

Consistent, high-quality contributors may be invited to become maintainers with additional privileges and responsibilities.

---

## 📚 Resources for Contributors

### Learning Resources

- [PHP The Right Way](https://phptherightway.com/)
- [PSR Standards](https://www.php-fig.org/psr/)
- [Git Tutorial](https://git-scm.com/docs/gittutorial)
- [Markdown Guide](https://www.markdownguide.org/)

### Project Resources

- 📖 [Documentation](../README.md)
- 🏗️ [Architecture Guide](architecture.md)
- 💻 [Code Examples](examples.md)
- 🎯 [Issue Tracker](https://github.com/juzdy/juzdy/issues)

---

## ❓ Questions?

Need help or have questions about contributing?

- 💬 [Start a Discussion](https://github.com/juzdy/juzdy/discussions)
- 📧 Contact maintainers (see GitHub profile)
- 📖 Read the [documentation](../README.md)

---

## 🙏 Thank You!

Your contributions make JUZDY better for everyone. Whether you're fixing a typo in documentation or implementing a major feature, every contribution matters.

**Happy coding!** 🚀

---

<p align="center">
  <strong>Ready to contribute?</strong><br>
  <a href="https://github.com/juzdy/juzdy/issues">Find an Issue to Work On →</a>
</p>
