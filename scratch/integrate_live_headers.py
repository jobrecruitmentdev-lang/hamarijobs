import os
import re

stories = [
    {
        "folder": "2026-seed-pitch-deck-architecture-capital-dynamics",
        "slug": "2026-seed-pitch-deck-architecture-capital-dynamics",
        "title": "2026 Seed Pitch Deck Architecture: Winning Capital Dynamics",
        "category": "Startup & Innovation",
        "readTime": "9 min read",
        "summary": "This guide defines the structural evolution of the seed pitch deck for 2026, emphasizing a 12-15 slide framework that balances narrative clarity with hard unit economics.",
        "image": "/images/guides/2026-seed-pitch-deck-architecture-capital-dynamics.jpg"
    },
    {
        "folder": "ai-agent-workflows-autonomous-operations-startups-2026",
        "slug": "ai-agent-workflows-autonomous-operations-startups-2026",
        "title": "AI Agent Workflows & Autonomous Ops for Startups in 2026",
        "category": "Startup & Innovation",
        "readTime": "9 min read",
        "summary": "In 2026, AI agents have evolved from passive prompt responders into autonomous orchestrators capable of planning and executing complex multi-step workflows for high-growth startups.",
        "image": "/images/guides/ai-agent-workflows-autonomous-operations-startups-2026.jpg"
    },
    {
        "folder": "b2b-abm-high-ticket-lead-scoring-2026",
        "slug": "b2b-abm-high-ticket-lead-scoring-2026",
        "title": "B2B ABM & High-Ticket Lead Scoring Strategy 2026",
        "category": "Marketing Strategy",
        "readTime": "9 min read",
        "summary": "This strategic guide outlines the convergence of Account-Based Marketing and advanced lead scoring for high-value B2B deals in 2026 with intent data integration.",
        "image": "/images/guides/b2b-abm-high-ticket-lead-scoring-2026.jpg"
    },
    {
        "folder": "multi-touch-attribution-cac-payback-optimization-2026",
        "slug": "multi-touch-attribution-cac-payback-optimization-2026",
        "title": "Multi-Touch Attribution & CAC Payback Optimization 2026",
        "category": "Marketing Strategy",
        "readTime": "9 min read",
        "summary": "This guide details how transitioning from single-touch to multi-touch attribution (MTA) directly optimizes Customer Acquisition Cost (CAC) payback periods for high-growth tech companies.",
        "image": "/images/guides/multi-touch-attribution-cac-payback-optimization-2026.jpg"
    }
]

base_dir = r"C:\hk\prmarketing\website\app\startup-stories"

for s in stories:
    page_file = os.path.join(base_dir, s["folder"], "page.tsx")
    if not os.path.exists(page_file):
        print(f"Skipping {page_file} (not found)")
        continue

    with open(page_file, "r", encoding="utf-8") as f:
        content = f.read()

    # Add StoryLiveHeader import if not present
    if "StoryLiveHeader" not in content:
        content = content.replace(
            'import { IconSparkles,',
            'import StoryLiveHeader from "@/components/StoryLiveHeader";\nimport { IconSparkles,'
        )

    # Replace <header>...</header> with <StoryLiveHeader ... />
    header_pattern = r'<header className="bg-gradient-to-b.*?</header>'
    replacement = f"""<StoryLiveHeader
          slug="{s['slug']}"
          initialTitle="{s['title']}"
          initialCategory="{s['category']}"
          initialReadTime="{s['readTime']}"
          initialSummary="{s['summary']}"
          imageSrc="{s['image']}"
        />"""

    new_content = re.sub(header_pattern, replacement, content, flags=re.DOTALL)
    
    # Also remove duplicate Executive Summary Box if it appears immediately after header in the body
    # (StoryLiveHeader renders it cleanly)
    with open(page_file, "w", encoding="utf-8") as f:
        f.write(new_content)
    print(f"Integrated StoryLiveHeader into {page_file}")

print("All 4 story pages updated with live dynamic header synchronization!")
