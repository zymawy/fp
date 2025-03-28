// Mock data with unique IDs across all pages
export const mockCausesData = [
  // Page 1
  [
    {
      id: 101,
      title: "Education Project",
      description: "Support education initiatives for underprivileged children",
      image: "https://images.unsplash.com/photo-1503676260728-1c00da094a0b?auto=format&fit=crop&q=80",
      raised: 15000,
      goal: 25000,
      donors: 234,
      category: "projects",
      projectType: "education",
      status: "ongoing",
      longDescription: "This initiative aims to provide quality education to underprivileged children who otherwise wouldn't have access to schooling. Your support helps provide school supplies, uniforms, and qualified teachers.",
      updates: [
        {
          date: "2024-03-15",
          title: "New Classroom Built",
          content: "Thanks to your generous donations, we've completed construction of a new classroom."
        },
        {
          date: "2024-03-01",
          title: "School Supplies Distributed",
          content: "Successfully distributed notebooks and stationery to 100 students."
        }
      ],
      startDate: "2024-01-01",
      endDate: "2024-12-31"
    },
    {
      id: 102,
      title: "Court Fee Payment",
      description: "Support for legal proceedings",
      image: "https://images.unsplash.com/photo-1589829545856-d10d557cf95f?auto=format&fit=crop&q=80",
      raised: 28000,
      goal: 40000,
      donors: 456,
      category: "judicialbills",
      invoiceNumber: "JB-2024-001",
      status: "pending",
      longDescription: "Help support individuals who cannot afford court fees for their legal proceedings. Your contribution ensures access to justice for those in need.",
      updates: [
        {
          date: "2024-03-10",
          title: "Case Update",
          content: "The court has scheduled the first hearing. Your support is making a difference."
        }
      ],
      startDate: "2024-02-01",
      endDate: "2024-04-30"
    },
    {
      id: 103,
      title: "Emergency Medical Aid",
      description: "Urgent medical assistance needed",
      image: "https://images.unsplash.com/photo-1516549655169-df83a0774514?auto=format&fit=crop&q=80",
      raised: 12000,
      goal: 30000,
      donors: 189,
      category: "rescue",
      rescueType: "medical",
      urgencyLevel: "high",
      location: "City Hospital",
      longDescription: "Emergency medical assistance is needed for critical care patients. Your immediate support can help save lives and provide essential medical care.",
      updates: [
        {
          date: "2024-03-18",
          title: "Medical Supplies Received",
          content: "Successfully procured essential medical supplies for emergency care."
        }
      ],
      startDate: "2024-03-15",
      endDate: "2024-04-15"
    }
  ],
  // Page 2
  [
    {
      id: 201,
      title: "Clean Water Initiative",
      description: "Provide clean water access to rural communities",
      image: "https://images.unsplash.com/photo-1519699047748-de8e457a634e?auto=format&fit=crop&q=80",
      raised: 45000,
      goal: 75000,
      donors: 567,
      category: "projects",
      projectType: "infrastructure",
      status: "ongoing",
      longDescription: "Help us bring clean water to rural communities. This project will install water purification systems and wells.",
      updates: [],
      startDate: "2024-02-15",
      endDate: "2024-08-15"
    },
    {
      id: 202,
      title: "Legal Aid Support",
      description: "Help with legal representation costs",
      image: "https://images.unsplash.com/photo-1453945619913-79ec89a82c51?auto=format&fit=crop&q=80",
      raised: 18000,
      goal: 35000,
      donors: 234,
      category: "judicialbills",
      invoiceNumber: "JB-2024-002",
      status: "pending",
      longDescription: "Support individuals seeking legal representation who cannot afford attorney fees.",
      updates: [],
      startDate: "2024-03-01",
      endDate: "2024-05-01"
    },
    {
      id: 203,
      title: "Disaster Relief",
      description: "Emergency support for natural disaster victims",
      image: "https://images.unsplash.com/photo-1469571486292-0ba58a3f068b?auto=format&fit=crop&q=80",
      raised: 89000,
      goal: 100000,
      donors: 1234,
      category: "rescue",
      rescueType: "disaster",
      urgencyLevel: "critical",
      location: "Coastal Region",
      longDescription: "Provide immediate relief to victims of recent natural disasters in coastal areas.",
      updates: [],
      startDate: "2024-03-10",
      endDate: "2024-04-10"
    }
  ],
  // Page 3
  [
    {
      id: 301,
      title: "Healthcare Access",
      description: "Support medical care for underserved communities",
      image: "https://images.unsplash.com/photo-1584515933487-779824d29309?auto=format&fit=crop&q=80",
      raised: 67000,
      goal: 120000,
      donors: 890,
      category: "projects",
      projectType: "healthcare",
      status: "ongoing",
      longDescription: "Expand healthcare access to underserved communities through mobile clinics and medical supplies.",
      updates: [],
      startDate: "2024-01-15",
      endDate: "2024-12-15"
    },
    {
      id: 302,
      title: "Family Court Support",
      description: "Assistance for family court proceedings",
      image: "https://images.unsplash.com/photo-1450101499163-c8848c66ca85?auto=format&fit=crop&q=80",
      raised: 12000,
      goal: 25000,
      donors: 145,
      category: "judicialbills",
      invoiceNumber: "JB-2024-003",
      status: "pending",
      longDescription: "Help families navigate legal proceedings with support for court fees and legal assistance.",
      updates: [],
      startDate: "2024-03-05",
      endDate: "2024-05-05"
    },
    {
      id: 303,
      title: "Emergency Transport",
      description: "Medical transport for critical patients",
      image: "https://images.unsplash.com/photo-1612277635895-20ab6699cb87?auto=format&fit=crop&q=80",
      raised: 34000,
      goal: 50000,
      donors: 456,
      category: "rescue",
      rescueType: "medical",
      urgencyLevel: "high",
      location: "Metropolitan Area",
      longDescription: "Provide emergency medical transport services for critical patients in need.",
      updates: [],
      startDate: "2024-03-12",
      endDate: "2024-04-12"
    }
  ]
];