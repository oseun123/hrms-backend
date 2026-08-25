<?php

namespace Database\Seeders;

use App\Models\Hris\Skill;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class SkillSeeder extends Seeder
{
    protected ?Tenant $tenant = null;

    public function __construct(?Tenant $tenant = null)
    {
        $this->tenant = $tenant;
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $skills = [
            // Technical Skills - Development
            ['name' => 'PHP', 'category' => 'Technical', 'description' => 'Server-side scripting language'],
            ['name' => 'Laravel', 'category' => 'Technical', 'description' => 'PHP Web Framework'],
            ['name' => 'JavaScript', 'category' => 'Technical', 'description' => 'Programming language for web development'],
            ['name' => 'TypeScript', 'category' => 'Technical', 'description' => 'Typed superset of JavaScript'],
            ['name' => 'React', 'category' => 'Technical', 'description' => 'JavaScript library for building user interfaces'],
            ['name' => 'Vue.js', 'category' => 'Technical', 'description' => 'Progressive JavaScript Framework'],
            ['name' => 'Angular', 'category' => 'Technical', 'description' => 'Platform for building mobile and desktop web applications'],
            ['name' => 'Node.js', 'category' => 'Technical', 'description' => 'JavaScript runtime built on Chrome\'s V8 JavaScript engine'],
            ['name' => 'Python', 'category' => 'Technical', 'description' => 'Interpreted, high-level, general-purpose programming language'],
            ['name' => 'Java', 'category' => 'Technical', 'description' => 'Class-based, object-oriented programming language'],
            ['name' => 'C#', 'category' => 'Technical', 'description' => 'General-purpose, multi-paradigm programming language'],
            ['name' => 'Go', 'category' => 'Technical', 'description' => 'Statically typed, compiled programming language designed at Google'],
            ['name' => 'Rust', 'category' => 'Technical', 'description' => 'Multi-paradigm programming language designed for performance and safety'],
            ['name' => 'SQL', 'category' => 'Technical', 'description' => 'Domain-specific language used in programming and designed for managing data'],

            // Design
            ['name' => 'UI/UX Design', 'category' => 'Design', 'description' => 'User Interface and User Experience Design'],
            ['name' => 'Figma', 'category' => 'Design', 'description' => 'Vector graphics editor and prototyping tool'],
            ['name' => 'Adobe XD', 'category' => 'Design', 'description' => 'Vector-based user experience design tool'],
            ['name' => 'Photoshop', 'category' => 'Design', 'description' => 'Raster graphics editor'],
            ['name' => 'Illustrator', 'category' => 'Design', 'description' => 'Vector graphics editor'],

            // Soft Skills
            ['name' => 'Communication', 'category' => 'Soft Skills', 'description' => 'Effective information transfer'],
            ['name' => 'Teamwork', 'category' => 'Soft Skills', 'description' => 'Collaborative effort of a group'],
            ['name' => 'Problem Solving', 'category' => 'Soft Skills', 'description' => 'The process of finding solutions to difficult or complex issues'],
            ['name' => 'Leadership', 'category' => 'Soft Skills', 'description' => 'The action of leading a group of people or an organization'],
            ['name' => 'Time Management', 'category' => 'Soft Skills', 'description' => 'The ability to use one\'s time effectively or productively'],
            ['name' => 'Critical Thinking', 'category' => 'Soft Skills', 'description' => 'Objective analysis and evaluation of an issue in order to form a judgment'],

            // Languages
            ['name' => 'English', 'category' => 'Language', 'description' => 'English Language'],
            ['name' => 'Spanish', 'category' => 'Language', 'description' => 'Spanish Language'],
            ['name' => 'French', 'category' => 'Language', 'description' => 'French Language'],
            ['name' => 'German', 'category' => 'Language', 'description' => 'German Language'],
            ['name' => 'Chinese', 'category' => 'Language', 'description' => 'Chinese Language'],

            // Project Management
            ['name' => 'Agile Methodology', 'category' => 'Project Management', 'description' => 'Software development methodology'],
            ['name' => 'Scrum', 'category' => 'Project Management', 'description' => 'Framework for project management'],
            ['name' => 'Kanban', 'category' => 'Project Management', 'description' => 'Scheduling system for lean manufacturing and just-in-time manufacturing'],
            ['name' => 'Jira', 'category' => 'Project Management', 'description' => 'Proprietary issue tracking product'],
            ['name' => 'Asana', 'category' => 'Project Management', 'description' => 'Web and mobile work management platform'],
            ['name' => 'Trello', 'category' => 'Project Management', 'description' => 'Web-based Kanban-style list-making application'],
            ['name' => 'Risk Management', 'category' => 'Project Management', 'description' => ' forecasting and evaluation of financial risks together with the identification of procedures to avoid or minimize their impact'],

            // DevOps & Cloud
            ['name' => 'Docker', 'category' => 'DevOps', 'description' => 'Set of platform as a service products that use OS-level virtualization to deliver software in packages called containers'],
            ['name' => 'Kubernetes', 'category' => 'DevOps', 'description' => 'Open-source container-orchestration system for automating computer application deployment, scaling, and management'],
            ['name' => 'AWS', 'category' => 'DevOps', 'description' => 'Amazon Web Services'],
            ['name' => 'Azure', 'category' => 'DevOps', 'description' => 'Microsoft Azure'],
            ['name' => 'Google Cloud Platform', 'category' => 'DevOps', 'description' => 'Suite of cloud computing services offered by Google'],
            ['name' => 'CI/CD', 'category' => 'DevOps', 'description' => 'Continuous Integration and Continuous Delivery'],
            ['name' => 'Terraform', 'category' => 'DevOps', 'description' => 'Infrastructure as code software tool'],
            ['name' => 'Jenkins', 'category' => 'DevOps', 'description' => 'Open source automation server'],

            // Data Science & Analytics
            ['name' => 'Machine Learning', 'category' => 'Data Science', 'description' => 'Study of computer algorithms that improve automatically through experience'],
            ['name' => 'Data Analysis', 'category' => 'Data Science', 'description' => 'Process of inspecting, cleansing, transforming and modeling data'],
            ['name' => 'R', 'category' => 'Data Science', 'description' => 'Programming language for statistical computing and graphics'],
            ['name' => 'Tableau', 'category' => 'Data Science', 'description' => 'Interactive data visualization software'],
            ['name' => 'Power BI', 'category' => 'Data Science', 'description' => 'Business analytics service by Microsoft'],
            ['name' => 'Pandas', 'category' => 'Data Science', 'description' => 'Software library written for the Python programming language for data manipulation and analysis'],

            // Mobile Development
            ['name' => 'Swift', 'category' => 'Mobile Development', 'description' => 'General-purpose, multi-paradigm, compiled programming language developed by Apple Inc.'],
            ['name' => 'Kotlin', 'category' => 'Mobile Development', 'description' => 'Cross-platform, statically typed, general-purpose programming language with type inference'],
            ['name' => 'Flutter', 'category' => 'Mobile Development', 'description' => 'Open-source UI software development kit created by Google'],
            ['name' => 'React Native', 'category' => 'Mobile Development', 'description' => 'Open-source mobile application framework created by Facebook'],
            ['name' => 'iOS Development', 'category' => 'Mobile Development', 'description' => 'Creating mobile applications for Apple hardware'],
            ['name' => 'Android Development', 'category' => 'Mobile Development', 'description' => 'Creating mobile applications for devices running on the Android operating system'],

            // Marketing
            ['name' => 'SEO', 'category' => 'Marketing', 'description' => 'Search Engine Optimization'],
            ['name' => 'Content Marketing', 'category' => 'Marketing', 'description' => 'Type of marketing that involves the creation and sharing of online material'],
            ['name' => 'Social Media Marketing', 'category' => 'Marketing', 'description' => 'Use of social media platforms and websites to promote a product or service'],
            ['name' => 'Google Analytics', 'category' => 'Marketing', 'description' => 'Web analytics service offered by Google'],
            ['name' => 'Email Marketing', 'category' => 'Marketing', 'description' => 'Act of sending a commercial message to a group of people, using email'],
            ['name' => 'Copywriting', 'category' => 'Marketing', 'description' => 'Act or occupation of writing text for the purpose of advertising or other forms of marketing'],

            // Sales & Business
            ['name' => 'Negotiation', 'category' => 'Sales', 'description' => 'Discussion aimed at reaching an agreement'],
            ['name' => 'CRM', 'category' => 'Sales', 'description' => 'Customer Relationship Management'],
            ['name' => 'Lead Generation', 'category' => 'Sales', 'description' => 'Initiation of consumer interest or inquiry into products or services of a business'],
            ['name' => 'B2B Sales', 'category' => 'Sales', 'description' => 'Business-to-business sales'],
            ['name' => 'Account Management', 'category' => 'Sales', 'description' => 'Post-sales role that focuses on nurturing client relationships'],
            ['name' => 'Business Strategy', 'category' => 'Business', 'description' => 'Working plan for achieving its vision, prioritizing objectives'],

            // Human Resources
            ['name' => 'Recruitment', 'category' => 'Human Resources', 'description' => 'Process of finding and hiring the best-qualified candidate'],
            ['name' => 'Employee Relations', 'category' => 'Human Resources', 'description' => 'Company\'s efforts to manage relationships between employers and employees'],
            ['name' => 'Payroll', 'category' => 'Human Resources', 'description' => 'List of employees and the payments due to each'],
            ['name' => 'Performance Management', 'category' => 'Human Resources', 'description' => 'Process of ensuring that a set of activities and outputs meets an organization\'s goals in an effective and efficient manner'],
            ['name' => 'HRIS', 'category' => 'Human Resources', 'description' => 'Human Resources Information System'],

            // Finance & Accounting
            ['name' => 'Financial Analysis', 'category' => 'Finance', 'description' => 'Assessment of the viability, stability, and profitability of a business'],
            ['name' => 'Bookkeeping', 'category' => 'Finance', 'description' => 'Recording of financial transactions'],
            ['name' => 'Tax Preparation', 'category' => 'Finance', 'description' => 'Process of preparing tax returns'],
            ['name' => 'Auditing', 'category' => 'Finance', 'description' => 'Official inspection of an individual\'s or organization\'s accounts'],
            ['name' => 'Budgeting', 'category' => 'Finance', 'description' => 'Process of creating a plan to spend your money'],

            // Office & Productivity
            ['name' => 'Microsoft Excel', 'category' => 'Productivity', 'description' => 'Spreadsheet developed by Microsoft'],
            ['name' => 'Microsoft Word', 'category' => 'Productivity', 'description' => 'Word processor developed by Microsoft'],
            ['name' => 'PowerPoint', 'category' => 'Productivity', 'description' => 'Presentation program'],
            ['name' => 'Google Workspace', 'category' => 'Productivity', 'description' => 'Collection of cloud computing, productivity and collaboration tools'],
            ['name' => 'Slack', 'category' => 'Productivity', 'description' => 'Channel-based messaging platform'],
        ];

        // Get all tenants
        $tenants = ($this->tenant && $this->tenant->exists) ? collect([$this->tenant]) : Tenant::all();

        foreach ($tenants as $tenant) {
            foreach ($skills as $skill) {
                Skill::firstOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'name' => $skill['name'],
                    ],
                    [
                        'category' => $skill['category'],
                        'description' => $skill['description'],
                        'is_active' => true,
                    ]
                );
            }
        }

        // If no tenants exist, output a message
        if ($tenants->count() === 0) {
            $this->command?->info('No tenants found. Skills were not seeded. Ensure tenants exist first.');
        }
    }
}
