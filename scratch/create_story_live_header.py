import os

header_component_path = r"C:\hk\prmarketing\website\components\StoryLiveHeader.tsx"

content = """"use client";

import { useState, useEffect } from "react";
import Link from "next/link";
import { IconSparkles } from "@/components/icons";

interface StoryLiveHeaderProps {
  slug: string;
  initialTitle: string;
  initialCategory: string;
  initialReadTime: string;
  initialSummary: string;
  imageSrc: string;
}

export default function StoryLiveHeader({
  slug,
  initialTitle,
  initialCategory,
  initialReadTime,
  initialSummary,
  imageSrc,
}: StoryLiveHeaderProps) {
  const [title, setTitle] = useState<string>(initialTitle);
  const [category, setCategory] = useState<string>(initialCategory);
  const [readTime, setReadTime] = useState<string>(initialReadTime);
  const [summary, setSummary] = useState<string>(initialSummary);
  const [coverImage, setCoverImage] = useState<string>(imageSrc);

  useEffect(() => {
    async function fetchLivePost() {
      try {
        const res = await fetch(`/api/v1/posts/${slug}?t=${Date.now()}`, {
          cache: "no-store",
          headers: { "Pragma": "no-cache" }
        });
        if (!res.ok) return;
        const json = await res.json();
        if (json && json.success && json.data) {
          const p = json.data;
          if (p.title) setTitle(p.title);
          if (p.category_name) setCategory(p.category_name);
          if (p.reading_time) setReadTime(p.reading_time);
          if (p.summary) setSummary(p.summary);
          if (p.cover_image) setCoverImage(p.cover_image);
        }
      } catch (e) {
        // Fallback to initial pre-rendered values
      }
    }

    fetchLivePost();
  }, [slug]);

  return (
    <header className="bg-gradient-to-b from-[#f9f6f0] via-white to-white py-14 sm:py-20">
      <div className="mx-auto max-w-4xl px-4 sm:px-6">
        <nav aria-label="Breadcrumb" className="mb-6 flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-slate-500">
          <Link href="/" className="hover:text-primary">Home</Link>
          <span>/</span>
          <Link href="/startup-stories/" className="hover:text-primary">Startup Stories</Link>
          <span>/</span>
          <span className="text-accent-dark">{category}</span>
        </nav>

        <span className="inline-flex items-center gap-1.5 rounded-full bg-primary-soft px-3.5 py-1.5 text-xs font-semibold text-primary">
          <IconSparkles width={14} height={14} />
          {category.toUpperCase()} BLUEPRINT (2026)
        </span>

        <h1 className="mt-6 font-heading text-3xl font-bold leading-tight tracking-tight text-ink sm:text-5xl">
          {title}
        </h1>

        <div className="mt-6 flex items-center gap-4 text-xs font-medium text-slate-500 border-b border-slate-200 pb-6">
          <span>By: PR Marketing Ventures Growth Team</span>
          <span>•</span>
          <span>Updated: September 2026</span>
          <span>•</span>
          <span>{readTime}</span>
        </div>

        {/* Featured Hero Banner Image (16:9 Cinema Framing) */}
        <div className="mt-8 overflow-hidden rounded-3xl border border-[#EDE4D8] bg-[#1f140e] shadow-md aspect-[16/9] w-full">
          <img
            src={coverImage}
            alt={title}
            className="h-full w-full object-cover object-center"
            loading="eager"
          />
        </div>

        {/* Executive Summary Box */}
        {summary && (
          <div className="mt-8 rounded-2xl border border-primary/20 bg-primary-soft/60 p-6">
            <p className="text-xs font-bold uppercase tracking-wider text-accent-dark">
              Executive Strategic Brief
            </p>
            <p className="mt-2 text-sm sm:text-base leading-relaxed text-slate-700 font-medium">
              {summary}
            </p>
          </div>
        )}
      </div>
    </header>
  );
}
"""

with open(header_component_path, "w", encoding="utf-8") as f:
    f.write(content.strip())

print(f"Created {header_component_path} successfully!")
