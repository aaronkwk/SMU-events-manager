USE `omni-db`;

DROP TABLE IF EXISTS `chat_reads`;
CREATE TABLE IF NOT EXISTS `chat_reads` (
  `user_id` int NOT NULL,
  `event_id` int NOT NULL,
  `last_seen` datetime NOT NULL,
  PRIMARY KEY (`user_id`,`event_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

DROP TABLE IF EXISTS `events`;
CREATE TABLE IF NOT EXISTS `events` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(128) NOT NULL,
  `category` varchar(128) NOT NULL,
  `date` date NOT NULL,
  `start_time` varchar(128) NOT NULL,
  `end_time` varchar(128) NOT NULL,
  `location` varchar(128) NOT NULL,
  `details` text,
  `picture` varchar(128) NOT NULL,
  `startISO` varchar(128) NOT NULL,
  `endISO` varchar(128) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `title`, `category`, `date`, `start_time`, `end_time`, `location`, `details`, `picture`, `startISO`, `endISO`) VALUES
(1, 'HackSMU: 24-Hour Hackathon', 'tech', '2025-12-05', '7:00 PM', '7:00 PM', 'SIS Building', 'Dive into 24 hours of intense coding and creation! Challenge yourself, collaborate with top talent, and build the next big thing in tech at the SIS Building.', 'pictures/hackathon.png', '2025-12-05T19:00:00+08:00', '2025-12-06T19:00:00+08:00'),
(2, 'Open Mic & Poetry Slam', 'arts', '2025-12-12', '8:00 PM', '10:30 PM', 'Concourse Hall', 'Step up to the mic or enjoy the show! A night dedicated to poetry, spoken word, and raw talent. Share your voice in the Concourse Hall or simply soak in the creative energy.', 'pictures/mic.jpeg', '2025-12-12T20:00:00+08:00', '2025-12-12T22:30:00+08:00'),
(3, 'Finance Forum: Markets 2025', 'career', '2025-12-10', '5:30 PM', '7:30 PM', 'Shaw Alumni House', 'Get an edge on the future of finance. Hear from industry leaders about the latest market trends and predictions for 2025. A critical career development event at Shaw Alumni House.', 'pictures/finance.png', '2025-12-10T17:30:00+08:00', '2025-12-10T19:30:00+08:00'),
(4, 'Skatathon 2025', 'sports', '2025-12-19', '7:00 PM', '9:00 PM', 'Fort Canning Park', 'Lace up and roll out! Join fellow skate enthusiasts for a fun, high-energy skate session at the scenic Fort Canning Park. All skill levels welcome.', 'pictures/skate.jpeg', '2025-12-19T19:00:00+08:00', '2025-12-19T21:00:00+08:00'),
(5, 'Art Jamming & Chill', 'arts', '2025-11-22', '2:00 PM', '5:00 PM', 'Tanjong Hall', 'Unleash your inner artist in a relaxed, no-pressure setting. Grab a brush, mingle, and create something beautiful. All materials provided at Tanjong Hall.', 'pictures/art.jpg', '2025-11-22T14:00:00+08:00', '2025-11-22T17:00:00+08:00'),
(6, 'AI & Robotics Demo Day', 'tech', '2025-12-06', '10:00 AM', '1:00 PM', 'SMU Labs', 'Witness the future of technology in action. See cutting-edge AI and robotics projects developed by students and researchers. An inspiring tech showcase at SMU Labs.', 'pictures/robotics.webp', '2025-12-06T10:00:00+08:00', '2025-12-06T13:00:00+08:00'),
(7, 'Eco-Smart Upcycling Workshop', 'arts', '2025-12-13', '3:00 PM', '6:00 PM', 'T3 Lobby', 'Learn how to give old items new life! Get hands-on with creative upcycling techniques and discuss sustainable practices. Join the eco-movement at T3 Lobby.', 'pictures/upcycling.jpg', '2025-12-13T15:00:00+08:00', '2025-12-13T18:00:00+08:00'),
(8, 'Career Coffee Chats', 'career', '2025-12-04', '4:00 PM', '6:00 PM', 'LKCSB Atrium', 'Casual conversation, big career insights. Meet with professionals from various industries in a relaxed setting over a cup of coffee at the LKCSB Atrium. Perfect for networking and advice.', 'pictures/chat.webp', '2025-12-04T16:00:00+08:00', '2025-12-04T18:00:00+08:00');

-- --------------------------------------------------------

--
-- Table structure for table `event_chat_channel`
--

DROP TABLE IF EXISTS `event_chat_channel`;
CREATE TABLE IF NOT EXISTS `event_chat_channel` (
  `event_id` int NOT NULL,
  `channel_url` varchar(255) NOT NULL,
  PRIMARY KEY (`event_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `event_person`
--

DROP TABLE IF EXISTS `event_person`;
CREATE TABLE IF NOT EXISTS `event_person` (
  `person_id` int NOT NULL,
  `event_id` int NOT NULL,
  `role` enum('creator','participant') DEFAULT 'participant',
  PRIMARY KEY (`person_id`,`event_id`),
  KEY `person_id` (`person_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `year` varchar(5) DEFAULT NULL,
  `school` varchar(100) DEFAULT NULL,
  `major` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `club` varchar(100) DEFAULT NULL,
  `points` int,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
