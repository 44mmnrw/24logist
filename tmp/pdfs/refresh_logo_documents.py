from pathlib import Path
import create_functional_characteristics as functional

functional.OUT = functional.OUT.with_name('логистРу — Функциональные характеристики — новый логотип.pdf')
functional.build_functional()
source = Path(__file__).with_name('create_platform_documents.py')
namespace = {'__file__': str(source)}
exec(compile(source.read_text(encoding='utf-8').split('\nresults = [')[0], str(source), 'exec'), namespace)
print(namespace['build'](
    'логистРу — Коммерческое предложение — новый логотип.pdf',
    'логистРу · Коммерческое предложение',
    [namespace['offer_1'], namespace['offer_2']],
))
