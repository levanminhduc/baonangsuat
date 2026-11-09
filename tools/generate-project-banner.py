#!/usr/bin/env python3
import os
import sys
import requests
import base64
from pathlib import Path

api_key = os.getenv('GEMINI_API_KEY')
if not api_key:
    print("Error: GEMINI_API_KEY not found in environment")
    sys.exit(1)

print("🔍 API key found")

prompt = """A modern factory productivity management dashboard interface in isometric 3D style:
- Central large digital display showing production metrics with Vietnamese text "Báo Năng Suất" 
- Multiple production lines with workers using tablets to input data
- Time tracking panels showing hourly milestones (7:00, 8:00, 9:00, etc.)
- Real-time bar charts and line graphs showing cumulative output
- Clean minimalist design with blue (#3b82f6) and white color scheme
- Professional high-quality illustration
- Smooth gradients and subtle shadows
- Wide panoramic banner format (16:9 aspect ratio in composition)
"""

print(f"🎨 Generating image with Gemini...")
print(f"   Prompt: {prompt[:100]}...")

url = f"https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-exp-image-generation:generateContent?key={api_key}"

headers = {
    "Content-Type": "application/json"
}

data = {
    "contents": [{
        "parts": [{
            "text": prompt
        }]
    }],
    "generationConfig": {
        "responseModalities": ["TEXT", "IMAGE"]
    }
}

try:
    response = requests.post(url, json=data, headers=headers, timeout=120)
    response.raise_for_status()
    
    result = response.json()
    
    if "candidates" not in result or len(result["candidates"]) == 0:
        print("✗ No candidates in response")
        print(f"Response: {result}")
        sys.exit(1)
    
    candidate = result["candidates"][0]
    parts = candidate.get("content", {}).get("parts", [])
    
    output_dir = Path('./img')
    output_dir.mkdir(parents=True, exist_ok=True)
    
    image_count = 0
    for i, part in enumerate(parts):
        if "inlineData" in part:
            image_count += 1
            image_data = part["inlineData"]["data"]
            mime_type = part["inlineData"].get("mimeType", "image/png")
            
            ext = "png" if "png" in mime_type else "jpg"
            save_path = output_dir / f'project-banner.{ext}'
            
            with open(save_path, 'wb') as f:
                f.write(base64.b64decode(image_data))
            
            print(f"✓ Image saved: {save_path}")
        elif "text" in part:
            print(f"📝 Text response: {part['text'][:200]}...")
    
    if image_count == 0:
        print("✗ No images generated")
        print(f"Parts: {parts}")
        sys.exit(1)
        
    print(f"\n✓ Successfully generated {image_count} image(s)")

except requests.exceptions.RequestException as e:
    print(f"\n✗ Error generating image: {e}")
    sys.exit(1)
except Exception as e:
    print(f"\n✗ Unexpected error: {e}")
    sys.exit(1)
