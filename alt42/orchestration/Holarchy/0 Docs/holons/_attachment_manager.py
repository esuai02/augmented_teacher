#!/usr/bin/env python3
"""
Attachment Manager for Holons
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
- 홀론 문서에 첨부파일 추가/삭제/조회
- 첨부파일 경로와 메타데이터 관리
- JSON과 마크다운 테이블 동기화
"""

import json
import re
import shutil
from pathlib import Path
from datetime import datetime
from typing import Optional, List, Dict
from dataclasses import dataclass


@dataclass
class Attachment:
    """첨부파일 정보"""
    name: str
    path: str
    type: str  # image, document, data, other
    description: str = ""
    added_at: str = ""
    
    def to_dict(self) -> dict:
        return {
            "name": self.name,
            "path": self.path,
            "type": self.type,
            "description": self.description,
            "added_at": self.added_at
        }
    
    @classmethod
    def from_dict(cls, data: dict) -> "Attachment":
        return cls(
            name=data.get("name", ""),
            path=data.get("path", ""),
            type=data.get("type", "other"),
            description=data.get("description", ""),
            added_at=data.get("added_at", "")
        )


class AttachmentManager:
    """첨부파일 관리자"""
    
    # 파일 타입 매핑
    TYPE_MAP = {
        # 이미지
        ".png": "image", ".jpg": "image", ".jpeg": "image", 
        ".gif": "image", ".webp": "image", ".svg": "image",
        # 문서
        ".pdf": "document", ".doc": "document", ".docx": "document",
        ".ppt": "document", ".pptx": "document", ".xls": "document",
        ".xlsx": "document", ".txt": "document", ".md": "document",
        # 데이터
        ".json": "data", ".csv": "data", ".xml": "data",
        ".yaml": "data", ".yml": "data",
        # 코드
        ".py": "code", ".js": "code", ".ts": "code",
        ".html": "code", ".css": "code",
    }
    
    def __init__(self, base_path: str):
        self.base_path = Path(base_path)
        self.holons_path = self.base_path
        self.attachments_folder = self.base_path / "_attachments"
        
        # 첨부파일 폴더 생성
        self.attachments_folder.mkdir(parents=True, exist_ok=True)
    
    def find_holon_file(self, holon_id: str) -> Optional[Path]:
        """holon_id로 파일 찾기"""
        # holons 폴더
        for md_file in self.holons_path.glob("*.md"):
            if md_file.name.startswith("_"):
                continue
            content = md_file.read_text(encoding="utf-8")
            if f'"holon_id": "{holon_id}"' in content:
                return md_file
        
        # meetings, decisions, tasks 폴더
        for folder in ["meetings", "decisions", "tasks"]:
            folder_path = self.holons_path.parent / folder
            if folder_path.exists():
                for md_file in folder_path.glob("*.md"):
                    content = md_file.read_text(encoding="utf-8")
                    if f'"holon_id": "{holon_id}"' in content:
                        return md_file
        
        return None
    
    def get_file_type(self, filepath: Path) -> str:
        """파일 타입 자동 감지"""
        suffix = filepath.suffix.lower()
        return self.TYPE_MAP.get(suffix, "other")
    
    def add_attachment(
        self, 
        holon_id: str, 
        source_path: str,
        description: str = "",
        copy_file: bool = True
    ) -> Optional[Attachment]:
        """홀론에 첨부파일 추가"""
        
        # 홀론 파일 찾기
        holon_file = self.find_holon_file(holon_id)
        if not holon_file:
            print(f"❌ 홀론을 찾을 수 없습니다: {holon_id}")
            return None
        
        source = Path(source_path)
        if not source.exists():
            print(f"❌ 파일을 찾을 수 없습니다: {source_path}")
            return None
        
        # 첨부파일 저장 경로 결정
        if copy_file:
            # 홀론별 폴더 생성
            holon_attach_folder = self.attachments_folder / holon_id
            holon_attach_folder.mkdir(parents=True, exist_ok=True)
            
            # 파일 복사
            dest = holon_attach_folder / source.name
            
            # 이름 충돌 처리
            counter = 1
            while dest.exists():
                stem = source.stem
                suffix = source.suffix
                dest = holon_attach_folder / f"{stem}_{counter}{suffix}"
                counter += 1
            
            shutil.copy2(source, dest)
            relative_path = f"_attachments/{holon_id}/{dest.name}"
        else:
            # 원본 경로 사용
            relative_path = str(source)
        
        # 첨부파일 정보 생성
        attachment = Attachment(
            name=source.name if copy_file else Path(source_path).name,
            path=relative_path,
            type=self.get_file_type(source),
            description=description,
            added_at=datetime.now().strftime("%Y-%m-%d %H:%M")
        )
        
        # JSON 업데이트
        content = holon_file.read_text(encoding="utf-8")
        json_match = re.search(r'```json\s*\n(.*?)\n```', content, re.DOTALL)
        
        if json_match:
            try:
                holon = json.loads(json_match.group(1))
                
                # attachments 배열 초기화 (없으면)
                if "attachments" not in holon:
                    holon["attachments"] = []
                
                holon["attachments"].append(attachment.to_dict())
                
                # JSON 업데이트
                new_json = json.dumps(holon, ensure_ascii=False, indent=2)
                new_content = content.replace(json_match.group(0), f"```json\n{new_json}\n```")
                
                # 마크다운 테이블 업데이트
                new_content = self._update_markdown_table(new_content, holon["attachments"])
                
                holon_file.write_text(new_content, encoding="utf-8")
                
                print(f"✅ 첨부파일 추가됨: {attachment.name}")
                print(f"   경로: {attachment.path}")
                print(f"   타입: {attachment.type}")
                
                return attachment
                
            except json.JSONDecodeError as e:
                print(f"❌ JSON 파싱 오류: {e}")
                return None
        
        return None
    
    def remove_attachment(
        self, 
        holon_id: str, 
        filename: str,
        delete_file: bool = False
    ) -> bool:
        """홀론에서 첨부파일 제거"""
        
        holon_file = self.find_holon_file(holon_id)
        if not holon_file:
            print(f"❌ 홀론을 찾을 수 없습니다: {holon_id}")
            return False
        
        content = holon_file.read_text(encoding="utf-8")
        json_match = re.search(r'```json\s*\n(.*?)\n```', content, re.DOTALL)
        
        if json_match:
            try:
                holon = json.loads(json_match.group(1))
                attachments = holon.get("attachments", [])
                
                # 파일명으로 찾기
                found_idx = None
                found_attachment = None
                for idx, att in enumerate(attachments):
                    if att["name"] == filename:
                        found_idx = idx
                        found_attachment = att
                        break
                
                if found_idx is None:
                    print(f"❌ 첨부파일을 찾을 수 없습니다: {filename}")
                    return False
                
                # 배열에서 제거
                attachments.pop(found_idx)
                holon["attachments"] = attachments
                
                # 실제 파일 삭제 (옵션)
                if delete_file and found_attachment:
                    file_path = self.holons_path / found_attachment["path"]
                    if file_path.exists():
                        file_path.unlink()
                        print(f"🗑️ 파일 삭제됨: {found_attachment['path']}")
                
                # JSON 업데이트
                new_json = json.dumps(holon, ensure_ascii=False, indent=2)
                new_content = content.replace(json_match.group(0), f"```json\n{new_json}\n```")
                
                # 마크다운 테이블 업데이트
                new_content = self._update_markdown_table(new_content, attachments)
                
                holon_file.write_text(new_content, encoding="utf-8")
                
                print(f"✅ 첨부파일 제거됨: {filename}")
                return True
                
            except json.JSONDecodeError as e:
                print(f"❌ JSON 파싱 오류: {e}")
                return False
        
        return False
    
    def list_attachments(self, holon_id: str) -> List[Attachment]:
        """홀론의 첨부파일 목록 조회"""
        
        holon_file = self.find_holon_file(holon_id)
        if not holon_file:
            print(f"❌ 홀론을 찾을 수 없습니다: {holon_id}")
            return []
        
        content = holon_file.read_text(encoding="utf-8")
        json_match = re.search(r'```json\s*\n(.*?)\n```', content, re.DOTALL)
        
        if json_match:
            try:
                holon = json.loads(json_match.group(1))
                attachments = holon.get("attachments", [])
                return [Attachment.from_dict(a) for a in attachments]
            except json.JSONDecodeError:
                return []
        
        return []
    
    def _update_markdown_table(self, content: str, attachments: List[dict]) -> str:
        """마크다운의 첨부파일 테이블 업데이트"""
        
        # 기존 테이블 패턴 찾기
        table_pattern = r'(## 📎 Attachments\s*\n.*?\n\| 파일명 \| 경로 \| 타입 \| 설명 \|\n\|[-]+\|[-]+\|[-]+\|[-]+\|\n)(?:.*?)(\n---|\n## )'
        
        # 새 테이블 내용 생성
        if attachments:
            rows = []
            for att in attachments:
                name = att.get("name", "")
                path = att.get("path", "")
                type_ = att.get("type", "other")
                desc = att.get("description", "")
                rows.append(f"| {name} | `{path}` | {type_} | {desc} |")
            table_rows = "\n".join(rows)
        else:
            table_rows = "| _(없음)_ | - | - | - |"
        
        # 테이블 교체
        def replace_table(match):
            return match.group(1) + table_rows + "\n" + match.group(2)
        
        new_content = re.sub(table_pattern, replace_table, content, flags=re.DOTALL)
        
        # 만약 테이블이 없으면 추가
        if "## 📎 Attachments" not in new_content:
            # Holonic Links 섹션 앞에 추가
            links_pattern = r'(---\s*\n)(## 🔗 Holonic Links)'
            attachment_section = f"""---

## 📎 Attachments

> 이 홀론과 관련된 첨부파일 목록입니다.  
> CLI로 관리: `python _cli.py attach add <holon_id> <파일경로>`

| 파일명 | 경로 | 타입 | 설명 |
|--------|------|------|------|
{table_rows}

"""
            new_content = re.sub(links_pattern, attachment_section + r'\2', new_content)
        
        return new_content
    
    def sync_all_holons(self) -> Dict[str, int]:
        """모든 홀론에 attachments 필드 추가 (없는 경우)"""
        
        result = {"updated": 0, "skipped": 0, "error": 0}
        
        all_folders = [self.holons_path]
        for folder in ["meetings", "decisions", "tasks"]:
            folder_path = self.holons_path.parent / folder
            if folder_path.exists():
                all_folders.append(folder_path)
        
        for folder in all_folders:
            for md_file in folder.glob("*.md"):
                if md_file.name.startswith("_"):
                    continue
                
                content = md_file.read_text(encoding="utf-8")
                json_match = re.search(r'```json\s*\n(.*?)\n```', content, re.DOTALL)
                
                if json_match:
                    try:
                        holon = json.loads(json_match.group(1))
                        
                        if "attachments" not in holon:
                            holon["attachments"] = []
                            
                            # JSON 업데이트
                            new_json = json.dumps(holon, ensure_ascii=False, indent=2)
                            new_content = content.replace(
                                json_match.group(0), 
                                f"```json\n{new_json}\n```"
                            )
                            
                            # 마크다운 테이블 추가
                            new_content = self._update_markdown_table(new_content, [])
                            
                            md_file.write_text(new_content, encoding="utf-8")
                            result["updated"] += 1
                        else:
                            result["skipped"] += 1
                            
                    except json.JSONDecodeError:
                        result["error"] += 1
        
        return result
    
    def print_attachments(self, holon_id: str) -> None:
        """첨부파일 목록 출력"""
        
        attachments = self.list_attachments(holon_id)
        
        print("=" * 60)
        print(f"📎 첨부파일 목록: {holon_id}")
        print("=" * 60)
        print()
        
        if not attachments:
            print("   (첨부파일 없음)")
            return
        
        print(f"{'번호':^4} {'파일명':^25} {'타입':^8} {'설명':^20}")
        print("-" * 60)
        
        for i, att in enumerate(attachments, 1):
            name = att.name[:23] + ".." if len(att.name) > 25 else att.name
            desc = att.description[:18] + ".." if len(att.description) > 20 else att.description
            print(f"{i:3}. {name:25} {att.type:^8} {desc:20}")
        
        print()
        print(f"총 {len(attachments)}개 파일")


def main():
    """테스트용"""
    import argparse
    
    parser = argparse.ArgumentParser(description="첨부파일 관리")
    parser.add_argument("action", choices=["add", "remove", "list", "sync"])
    parser.add_argument("--holon", "-H", help="holon_id")
    parser.add_argument("--file", "-f", help="파일 경로")
    parser.add_argument("--desc", "-d", default="", help="설명")
    parser.add_argument("--delete", action="store_true", help="실제 파일 삭제")
    
    args = parser.parse_args()
    
    script_dir = Path(__file__).parent
    manager = AttachmentManager(str(script_dir))
    
    if args.action == "add":
        if not args.holon or not args.file:
            print("❌ --holon과 --file 필요")
            return
        manager.add_attachment(args.holon, args.file, args.desc)
        
    elif args.action == "remove":
        if not args.holon or not args.file:
            print("❌ --holon과 --file 필요")
            return
        manager.remove_attachment(args.holon, args.file, args.delete)
        
    elif args.action == "list":
        if not args.holon:
            print("❌ --holon 필요")
            return
        manager.print_attachments(args.holon)
        
    elif args.action == "sync":
        print("🔄 모든 홀론에 attachments 필드 동기화...")
        result = manager.sync_all_holons()
        print(f"✅ 완료: {result['updated']}개 업데이트, {result['skipped']}개 스킵, {result['error']}개 오류")


if __name__ == "__main__":
    main()

