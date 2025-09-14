<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\LearningPath;
use Illuminate\Support\Str;

class LearningPlatformSeeder extends Seeder
{
    public function run(): void
    {
        // Create sample courses
        $webDevCourse = Course::create([
            'title' => 'Complete Web Development Bootcamp',
            'slug' => 'complete-web-development-bootcamp',
            'description' => 'Learn full-stack web development from scratch. Master HTML, CSS, JavaScript, PHP, and MySQL to build dynamic websites.',
            'short_description' => 'Complete web development course covering frontend and backend technologies.',
            'category' => 'Web Development',
            'difficulty' => 'beginner',
            'type' => 'premium',
            'price' => 99.99,
            'language' => 'en',
            'duration_hours' => 40,
            'learning_objectives' => [
                'Build responsive websites with HTML & CSS',
                'Create interactive web pages with JavaScript',
                'Develop backend applications with PHP',
                'Design and manage databases with MySQL',
                'Deploy applications to production servers'
            ],
            'prerequisites' => [
                'Basic computer literacy',
                'Willingness to learn'
            ],
            'instructor_name' => 'Dr. Sarah Johnson',
            'instructor_bio' => 'Senior Web Developer with 10+ years of experience in full-stack development.',
            'thumbnail_url' => '/images/courses/web-dev-thumb.jpg',
            'video_intro_url' => '/videos/courses/web-dev-intro.mp4',
            'has_certificate' => true,
            'is_published' => true,
            'enrolled_count' => 1250,
            'rating' => 4.8,
            'reviews_count' => 234
        ]);

        $pythonCourse = Course::create([
            'title' => 'Python Programming Masterclass',
            'slug' => 'python-programming-masterclass',
            'description' => 'Master Python programming from basics to advanced concepts. Learn data structures, OOP, web development, and data science.',
            'short_description' => 'Comprehensive Python course for beginners to advanced developers.',
            'category' => 'Programming',
            'difficulty' => 'intermediate',
            'type' => 'premium',
            'price' => 149.99,
            'language' => 'en',
            'duration_hours' => 60,
            'learning_objectives' => [
                'Master Python syntax and fundamentals',
                'Understand object-oriented programming',
                'Build web applications with Django/Flask',
                'Work with data using pandas and numpy',
                'Create automation scripts'
            ],
            'prerequisites' => [
                'Basic programming knowledge helpful',
                'Computer with Python installed'
            ],
            'instructor_name' => 'Prof. Michael Chen',
            'instructor_bio' => 'Computer Science Professor and Python expert with 15+ years teaching experience.',
            'thumbnail_url' => '/images/courses/python-thumb.jpg',
            'video_intro_url' => '/videos/courses/python-intro.mp4',
            'has_certificate' => true,
            'is_published' => true,
            'enrolled_count' => 2100,
            'rating' => 4.9,
            'reviews_count' => 456
        ]);

        $reactCourse = Course::create([
            'title' => 'Modern React Development',
            'slug' => 'modern-react-development',
            'description' => 'Learn React from the ground up. Build modern, responsive web applications using React hooks, context, and popular libraries.',
            'short_description' => 'Master React.js and build professional web applications.',
            'category' => 'Frontend Development',
            'difficulty' => 'intermediate',
            'type' => 'premium',
            'price' => 129.99,
            'language' => 'en',
            'duration_hours' => 35,
            'learning_objectives' => [
                'Build React applications from scratch',
                'Master React hooks and state management',
                'Implement routing with React Router',
                'Integrate with APIs and manage async data',
                'Deploy React apps to production'
            ],
            'prerequisites' => [
                'Solid JavaScript knowledge',
                'Basic HTML/CSS skills',
                'Understanding of ES6+ features'
            ],
            'instructor_name' => 'Alex Rodriguez',
            'instructor_bio' => 'Senior Frontend Developer at Google with expertise in React and modern web technologies.',
            'thumbnail_url' => '/images/courses/react-thumb.jpg',
            'video_intro_url' => '/videos/courses/react-intro.mp4',
            'has_certificate' => true,
            'is_published' => true,
            'enrolled_count' => 890,
            'rating' => 4.7,
            'reviews_count' => 178
        ]);

        $freeCourse = Course::create([
            'title' => 'Introduction to Programming',
            'slug' => 'introduction-to-programming',
            'description' => 'Start your programming journey with this free introductory course. Learn basic concepts and get hands-on experience.',
            'short_description' => 'Free introduction to programming concepts and basics.',
            'category' => 'Programming Fundamentals',
            'difficulty' => 'beginner',
            'type' => 'free',
            'price' => 0.00,
            'language' => 'en',
            'duration_hours' => 10,
            'learning_objectives' => [
                'Understand programming fundamentals',
                'Learn about variables and data types',
                'Practice basic problem-solving',
                'Write your first programs'
            ],
            'prerequisites' => [],
            'instructor_name' => 'Emily Foster',
            'instructor_bio' => 'Programming instructor with a passion for teaching beginners.',
            'thumbnail_url' => '/images/courses/intro-programming-thumb.jpg',
            'video_intro_url' => '/videos/courses/intro-programming.mp4',
            'has_certificate' => false,
            'is_published' => true,
            'enrolled_count' => 5670,
            'rating' => 4.5,
            'reviews_count' => 892
        ]);

        // Create modules for Web Development course
        $this->createWebDevModules($webDevCourse);

        // Create modules for Python course
        $this->createPythonModules($pythonCourse);

        // Create modules for React course
        $this->createReactModules($reactCourse);

        // Create modules for free course
        $this->createIntroModules($freeCourse);

        // Create learning paths
        $this->createLearningPaths($webDevCourse, $pythonCourse, $reactCourse, $freeCourse);
    }

    private function createWebDevModules($course)
    {
        $modules = [
            [
                'title' => 'HTML Fundamentals',
                'description' => 'Learn the building blocks of web pages',
                'type' => 'video',
                'content' => 'Introduction to HTML tags, elements, and structure',
                'video_url' => '/videos/modules/html-fundamentals.mp4',
                'duration_minutes' => 45,
                'sort_order' => 1,
                'is_preview' => true,
            ],
            [
                'title' => 'CSS Styling and Layout',
                'description' => 'Master CSS for beautiful web designs',
                'type' => 'video',
                'content' => 'CSS selectors, properties, flexbox, and grid',
                'video_url' => '/videos/modules/css-styling.mp4',
                'duration_minutes' => 60,
                'sort_order' => 2,
                'is_preview' => false,
            ],
            [
                'title' => 'JavaScript Basics',
                'description' => 'Add interactivity to your websites',
                'type' => 'video',
                'content' => 'Variables, functions, DOM manipulation',
                'video_url' => '/videos/modules/js-basics.mp4',
                'duration_minutes' => 90,
                'sort_order' => 3,
                'is_preview' => false,
            ],
            [
                'title' => 'Build Your First Website',
                'description' => 'Hands-on coding exercise',
                'type' => 'coding_exercise',
                'content' => 'Create a personal portfolio website',
                'coding_template' => [
                    'html' => '<!DOCTYPE html>\n<html>\n<head>\n    <title>My Portfolio</title>\n</head>\n<body>\n    <!-- Add your content here -->\n</body>\n</html>',
                    'css' => '/* Add your styles here */',
                    'js' => '// Add your JavaScript here'
                ],
                'duration_minutes' => 120,
                'sort_order' => 4,
                'is_preview' => false,
            ],
            [
                'title' => 'PHP Backend Development',
                'description' => 'Server-side programming with PHP',
                'type' => 'video',
                'content' => 'PHP syntax, variables, functions, and forms',
                'video_url' => '/videos/modules/php-backend.mp4',
                'duration_minutes' => 75,
                'sort_order' => 5,
                'is_preview' => false,
            ],
            [
                'title' => 'Database Design with MySQL',
                'description' => 'Learn database fundamentals',
                'type' => 'video',
                'content' => 'Tables, relationships, queries, and optimization',
                'video_url' => '/videos/modules/mysql-database.mp4',
                'duration_minutes' => 90,
                'sort_order' => 6,
                'is_preview' => false,
            ],
            [
                'title' => 'Final Project: Job Board Website',
                'description' => 'Build a complete job board application',
                'type' => 'project',
                'content' => 'Create a full-featured job board with user registration, job posting, and search functionality',
                'duration_minutes' => 300,
                'sort_order' => 7,
                'is_preview' => false,
            ],
        ];

        foreach ($modules as $moduleData) {
            $course->modules()->create($moduleData);
        }
    }

    private function createPythonModules($course)
    {
        $modules = [
            [
                'title' => 'Python Installation and Setup',
                'description' => 'Get started with Python development environment',
                'type' => 'video',
                'content' => 'Installing Python, IDE setup, and running your first program',
                'video_url' => '/videos/modules/python-setup.mp4',
                'duration_minutes' => 30,
                'sort_order' => 1,
                'is_preview' => true,
            ],
            [
                'title' => 'Python Syntax and Variables',
                'description' => 'Learn Python fundamentals',
                'type' => 'coding_exercise',
                'content' => 'Variables, data types, and basic operations',
                'coding_template' => [
                    'python' => '# Welcome to Python programming!\n# Let\'s start with variables\n\nname = "Your Name"\nage = 25\n\nprint(f"Hello {name}, you are {age} years old!")'
                ],
                'duration_minutes' => 45,
                'sort_order' => 2,
                'is_preview' => false,
            ],
            [
                'title' => 'Control Structures and Loops',
                'description' => 'Master Python control flow',
                'type' => 'video',
                'content' => 'If statements, for loops, while loops, and conditional logic',
                'video_url' => '/videos/modules/python-control.mp4',
                'duration_minutes' => 60,
                'sort_order' => 3,
                'is_preview' => false,
            ],
            [
                'title' => 'Functions and Modules',
                'description' => 'Organize your Python code',
                'type' => 'coding_exercise',
                'content' => 'Creating functions, importing modules, and code organization',
                'coding_template' => [
                    'python' => 'def greet(name):\n    """Function to greet a user"""\n    return f"Hello, {name}!"\n\n# Test your function here\nprint(greet("World"))'
                ],
                'duration_minutes' => 75,
                'sort_order' => 4,
                'is_preview' => false,
            ],
            [
                'title' => 'Object-Oriented Programming',
                'description' => 'Learn OOP concepts in Python',
                'type' => 'video',
                'content' => 'Classes, objects, inheritance, and polymorphism',
                'video_url' => '/videos/modules/python-oop.mp4',
                'duration_minutes' => 90,
                'sort_order' => 5,
                'is_preview' => false,
            ],
        ];

        foreach ($modules as $moduleData) {
            $course->modules()->create($moduleData);
        }
    }

    private function createReactModules($course)
    {
        $modules = [
            [
                'title' => 'React Fundamentals',
                'description' => 'Understanding React components and JSX',
                'type' => 'video',
                'content' => 'Components, JSX syntax, and React ecosystem',
                'video_url' => '/videos/modules/react-fundamentals.mp4',
                'duration_minutes' => 50,
                'sort_order' => 1,
                'is_preview' => true,
            ],
            [
                'title' => 'State and Props',
                'description' => 'Managing data in React applications',
                'type' => 'coding_exercise',
                'content' => 'Component state, props, and data flow',
                'coding_template' => [
                    'react' => 'import React, { useState } from \'react\';\n\nfunction Counter() {\n  const [count, setCount] = useState(0);\n\n  return (\n    <div>\n      <p>Count: {count}</p>\n      <button onClick={() => setCount(count + 1)}>Increment</button>\n    </div>\n  );\n}\n\nexport default Counter;'
                ],
                'duration_minutes' => 65,
                'sort_order' => 2,
                'is_preview' => false,
            ],
            [
                'title' => 'React Hooks Deep Dive',
                'description' => 'Master modern React with hooks',
                'type' => 'video',
                'content' => 'useState, useEffect, useContext, and custom hooks',
                'video_url' => '/videos/modules/react-hooks.mp4',
                'duration_minutes' => 80,
                'sort_order' => 3,
                'is_preview' => false,
            ],
            [
                'title' => 'Building a Todo App',
                'description' => 'Practical React application development',
                'type' => 'project',
                'content' => 'Build a complete todo application with CRUD operations',
                'duration_minutes' => 180,
                'sort_order' => 4,
                'is_preview' => false,
            ],
        ];

        foreach ($modules as $moduleData) {
            $course->modules()->create($moduleData);
        }
    }

    private function createIntroModules($course)
    {
        $modules = [
            [
                'title' => 'What is Programming?',
                'description' => 'Introduction to programming concepts',
                'type' => 'video',
                'content' => 'Understanding what programming is and why it\'s important',
                'video_url' => '/videos/modules/what-is-programming.mp4',
                'duration_minutes' => 20,
                'sort_order' => 1,
                'is_preview' => true,
            ],
            [
                'title' => 'Problem-Solving with Code',
                'description' => 'Learn to think like a programmer',
                'type' => 'text',
                'content' => 'Breaking down problems into smaller, manageable pieces and creating step-by-step solutions.',
                'duration_minutes' => 30,
                'sort_order' => 2,
                'is_preview' => true,
            ],
            [
                'title' => 'Your First Program',
                'description' => 'Write and run your first program',
                'type' => 'coding_exercise',
                'content' => 'Create a simple "Hello World" program',
                'coding_template' => [
                    'python' => '# Your first Python program\n# Change the message below and run the code\n\nprint("Hello, World!")'
                ],
                'duration_minutes' => 25,
                'sort_order' => 3,
                'is_preview' => true,
            ],
        ];

        foreach ($modules as $moduleData) {
            $course->modules()->create($moduleData);
        }
    }

    private function createLearningPaths($webDevCourse, $pythonCourse, $reactCourse, $freeCourse)
    {
        // Full-Stack Developer Path
        LearningPath::create([
            'title' => 'Full-Stack Web Developer',
            'slug' => 'full-stack-web-developer',
            'description' => 'Complete learning path to become a full-stack web developer. Start from basics and build up to advanced concepts.',
            'category' => 'Web Development',
            'level' => 'beginner',
            'course_ids' => [$freeCourse->id, $webDevCourse->id, $reactCourse->id],
            'total_duration_hours' => 85,
            'thumbnail_url' => '/images/paths/full-stack-thumb.jpg',
            'is_published' => true,
            'enrolled_count' => 567
        ]);

        // Python Developer Path
        LearningPath::create([
            'title' => 'Python Programming Specialist',
            'slug' => 'python-programming-specialist',
            'description' => 'Master Python programming from fundamentals to advanced applications in web development and data science.',
            'category' => 'Programming',
            'level' => 'intermediate',
            'course_ids' => [$freeCourse->id, $pythonCourse->id],
            'total_duration_hours' => 70,
            'thumbnail_url' => '/images/paths/python-specialist-thumb.jpg',
            'is_published' => true,
            'enrolled_count' => 423
        ]);

        // Frontend Developer Path
        LearningPath::create([
            'title' => 'Frontend Development Expert',
            'slug' => 'frontend-development-expert',
            'description' => 'Become an expert in frontend development with modern tools and frameworks like React.',
            'category' => 'Frontend Development',
            'level' => 'intermediate',
            'course_ids' => [$webDevCourse->id, $reactCourse->id],
            'total_duration_hours' => 75,
            'thumbnail_url' => '/images/paths/frontend-expert-thumb.jpg',
            'is_published' => true,
            'enrolled_count' => 334
        ]);
    }
}