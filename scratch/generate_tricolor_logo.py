import math
import os
from PIL import Image, ImageDraw, ImageFilter

def render_logo(output_png_path, size=512):
    # Render at 4x resolution for pristine antialiasing
    scale = 4
    w, h = size * scale, size * scale
    cx, cy = w / 2, h / 2
    
    img = Image.new("RGBA", (w, h), (0, 0, 0, 0))
    draw = ImageDraw.Draw(img)
    
    # Radius definitions
    r_outer = 220 * scale
    r_inner = 180 * scale
    r_chakra = 80 * scale
    
    # 1. Outer Saffron Arc (Top half 180 to 360 deg)
    # Saffron: #FF9933 -> #E65100
    saffron_color = (255, 153, 51, 255)
    green_color = (19, 136, 8, 255)
    navy_color = (0, 0, 128, 255)
    gold_color = (212, 175, 55, 255)
    white_color = (255, 255, 255, 255)
    
    # Draw Outer Ring with Gold Borders
    # Outer gold circle
    draw.ellipse([cx - r_outer - 8*scale, cy - r_outer - 8*scale, cx + r_outer + 8*scale, cy + r_outer + 8*scale], outline=gold_color, width=6*scale)
    draw.ellipse([cx - r_outer, cy - r_outer, cx + r_outer, cy + r_outer], fill=(255, 255, 255, 240), outline=gold_color, width=4*scale)
    
    # Draw Saffron Upper Crescent (Top 180 deg)
    bbox_outer = [cx - r_outer, cy - r_outer, cx + r_outer, cy + r_outer]
    draw.pieslice(bbox_outer, start=180, end=360, fill=saffron_color)
    
    # Draw India Green Lower Crescent (Bottom 180 deg)
    draw.pieslice(bbox_outer, start=0, end=180, fill=green_color)
    
    # Inner White Core Circle
    draw.ellipse([cx - r_inner, cy - r_inner, cx + r_inner, cy + r_inner], fill=white_color, outline=gold_color, width=5*scale)
    
    # 2. Golden Stambh / Pillar Capital Tribute Motif (Stylized 3-Pillars Silhouette)
    # Stambh Base Abacus (Gold)
    base_w = 90 * scale
    base_h = 16 * scale
    base_y = cy - 25 * scale
    draw.rounded_rectangle([cx - base_w, base_y, cx + base_w, base_y + base_h], radius=4*scale, fill=gold_color)
    
    # 3 Stylized Pillar Stems
    stem_w = 18 * scale
    stem_h = 60 * scale
    stem_top_y = base_y - stem_h
    # Center pillar
    draw.rounded_rectangle([cx - stem_w/2, stem_top_y, cx + stem_w/2, base_y], radius=3*scale, fill=gold_color)
    # Left pillar
    draw.rounded_rectangle([cx - base_w*0.65 - stem_w/2, stem_top_y + 10*scale, cx - base_w*0.65 + stem_w/2, base_y], radius=3*scale, fill=gold_color)
    # Right pillar
    draw.rounded_rectangle([cx + base_w*0.65 - stem_w/2, stem_top_y + 10*scale, cx + base_w*0.65 + stem_w/2, base_y], radius=3*scale, fill=gold_color)
    
    # Stambh Capital Crest (3 Stylized Lion / Flame Crowns)
    # Center Crown
    draw.polygon([
        (cx - 28*scale, stem_top_y),
        (cx - 36*scale, stem_top_y - 28*scale),
        (cx - 16*scale, stem_top_y - 45*scale),
        (cx, stem_top_y - 30*scale),
        (cx + 16*scale, stem_top_y - 45*scale),
        (cx + 36*scale, stem_top_y - 28*scale),
        (cx + 28*scale, stem_top_y)
    ], fill=gold_color)
    # Center Crest Jewel / Star
    draw.ellipse([cx - 8*scale, stem_top_y - 18*scale, cx + 8*scale, stem_top_y - 2*scale], fill=navy_color)
    
    # Left Crown
    draw.polygon([
        (cx - base_w*0.65 - 20*scale, stem_top_y + 10*scale),
        (cx - base_w*0.65 - 26*scale, stem_top_y - 12*scale),
        (cx - base_w*0.65, stem_top_y - 24*scale),
        (cx - base_w*0.65 + 16*scale, stem_top_y - 8*scale),
        (cx - base_w*0.65 + 14*scale, stem_top_y + 10*scale)
    ], fill=gold_color)
    
    # Right Crown
    draw.polygon([
        (cx + base_w*0.65 - 14*scale, stem_top_y + 10*scale),
        (cx + base_w*0.65 - 16*scale, stem_top_y - 8*scale),
        (cx + base_w*0.65, stem_top_y - 24*scale),
        (cx + base_w*0.65 + 26*scale, stem_top_y - 12*scale),
        (cx + base_w*0.65 + 20*scale, stem_top_y + 10*scale)
    ], fill=gold_color)

    # 3. Exact 24-Spoke Navy Ashok Chakra (Placed gracefully at the core base)
    chakra_cy = cy + 55 * scale
    chakra_r = 65 * scale
    
    # Chakra Outer Ring
    draw.ellipse([cx - chakra_r, chakra_cy - chakra_r, cx + chakra_r, chakra_cy + chakra_r], outline=navy_color, width=5*scale)
    draw.ellipse([cx - chakra_r + 8*scale, chakra_cy - chakra_r + 8*scale, cx + chakra_r - 8*scale, chakra_cy + chakra_r - 8*scale], outline=navy_color, width=2*scale)
    
    # Chakra Center Hub
    draw.ellipse([cx - 12*scale, chakra_cy - 12*scale, cx + 12*scale, chakra_cy + 12*scale], fill=navy_color)
    draw.ellipse([cx - 5*scale, chakra_cy - 5*scale, cx + 5*scale, chakra_cy + 5*scale], fill=white_color)
    
    # 24 Spokes (15 degrees apart)
    for i in range(24):
        angle = math.radians(i * 15)
        # Inner start point
        x1 = cx + (12 * scale) * math.cos(angle)
        y1 = chakra_cy + (12 * scale) * math.sin(angle)
        # Outer end point
        x2 = cx + (chakra_r - 8 * scale) * math.cos(angle)
        y2 = chakra_cy + (chakra_r - 8 * scale) * math.sin(angle)
        draw.line([(x1, y1), (x2, y2)], fill=navy_color, width=3*scale)
        
        # Micro circular bead on outer rim between spokes
        angle_bead = math.radians(i * 15 + 7.5)
        bx = cx + (chakra_r - 4 * scale) * math.cos(angle_bead)
        by = chakra_cy + (chakra_r - 4 * scale) * math.sin(angle_bead)
        draw.ellipse([bx - 2*scale, by - 2*scale, bx + 2*scale, by + 2*scale], fill=navy_color)

    # 4. Laurel Leaves / Wheat Garland on Bottom Flanks (Gold)
    # Left wreath arc
    for i in range(7):
        ang = math.radians(110 + i * 11)
        lx = cx + (r_inner + 18 * scale) * math.cos(ang)
        ly = cy + (r_inner + 18 * scale) * math.sin(ang)
        draw.ellipse([lx - 9*scale, ly - 6*scale, lx + 9*scale, ly + 6*scale], fill=gold_color)
        
    # Right wreath arc
    for i in range(7):
        ang = math.radians(70 - i * 11)
        rx = cx + (r_inner + 18 * scale) * math.cos(ang)
        ry = cy + (r_inner + 18 * scale) * math.sin(ang)
        draw.ellipse([rx - 9*scale, ry - 6*scale, rx + 9*scale, ry + 6*scale], fill=gold_color)

    # Downsample with high-quality Lanczos filter for razor-sharp antialiasing
    final_img = img.resize((size, size), Image.Resampling.LANCZOS)
    os.makedirs(os.path.dirname(output_png_path), exist_ok=True)
    final_img.save(output_png_path, "PNG")
    print(f"Rendered {output_png_path} ({size}x{size}) successfully!")

if __name__ == "__main__":
    dest1 = r"c:\hk\hamarijobs\frontend\public\assets\images\logo.png"
    dest2 = r"c:\hk\hamarijobs\backend\public\assets\images\logo.png"
    render_logo(dest1, 512)
    render_logo(dest2, 512)
